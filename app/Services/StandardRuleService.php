<?php

namespace App\Services;

use App\Models\Operation;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\Rule;
use App\Models\Subprocess;
use App\Traits\ModelTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Regla estándar de una referencia en un puesto de trabajo.
 *
 * Una regla aplica si:
 * - su proceso es el del puesto (obligatorio);
 * - si tiene subproceso u operación, son los del puesto;
 * - cada maestro que tenga (marca, grupo, categoría, subcategoría, subgrupo, color…) coincide con la referencia.
 *
 * Entre las que aplican gana la más específica: la que suma más puntos según los pesos de los maestros
 * (items.settings.weights del ítem de Reglas). En un empate, la de alcance más preciso (operación > subproceso).
 */
class StandardRuleService
{
    use ModelTypes;

    /**
     * Firma de cada regla activa (id => "clave:id|clave:id", claves ordenadas), para no repetir una combinación de
     * relaciones. $except_id deja fuera la regla que se está editando.
     */
    public function activeSignatures(?int $except_id = null): Collection
    {
        $rule_ids = DB::table('subitems')
            ->where('item_id', Rule::ITEM_ID)
            ->when($except_id, fn ($query) => $query->where('id', '!=', $except_id))
            ->whereNull('deleted_at')
            ->pluck('id');

        return $this->signaturesOf($rule_ids->all());
    }

    /**
     * Firma de una regla, aunque esté borrada (para revisar si al restaurarla choca con una activa).
     */
    public function signatureOf(int $rule_id): string
    {
        return $this->signaturesOf([$rule_id])->get($rule_id, '');
    }

    protected function signaturesOf(array $rule_ids): Collection
    {
        $class_to_key = collect($this->getModelTypesFromRules())->mapWithKeys(fn ($config, $key) => [$config['model'] => $key]);

        return DB::table('model_has_subitems')
            ->whereIn('subitem_id', $rule_ids)
            ->get(['subitem_id', 'model_type', 'model_id'])
            ->groupBy('subitem_id')
            ->map(function ($rows) use ($class_to_key) {
                return $rows
                    ->mapWithKeys(function ($row) use ($class_to_key) {
                        $class = Relation::getMorphedModel($row->model_type) ?? $row->model_type;
                        $key = $class_to_key[$class] ?? null;

                        return $key ? [$key => $row->model_id] : [];
                    })
                    ->sortKeys()
                    ->map(fn ($value, $key) => "$key:$value")
                    ->implode('|');
            });
    }

    /**
     * Valores de la referencia por maestro (clave de getModelTypesFromRules => id).
     */
    public function referenceValues(ProductionOrder $production_order): array
    {
        $sheet = $production_order->technical_sheet;
        $product = $sheet?->product;

        return array_filter([
            'trademark' => $product?->trademark_id,
            'group' => $product?->trademark?->group()->first()?->id,
            'subcategory' => $product?->subcategory_id,
            'category' => $product?->subcategory?->category()->first()?->id,
            'subgroup' => $sheet?->subgroup_id,
            'garment_type' => $sheet?->garment_type_id,
            'boot_type' => $sheet?->boot_type_id,
            'back_type' => $sheet?->back_type_id,
            'waistband_type' => $sheet?->waistband_type_id,
            'wash_tone' => $sheet?->wash_tone_id,
            'color' => $sheet?->color_id,
            'collection' => $sheet?->collection_id,
            'yoke_type' => $sheet?->yoke_type_id,
        ], fn ($value) => filled($value));
    }

    /**
     * Devuelve ['rule' => Rule, 'points' => int, 'matched' => [claves]] o null si ninguna aplica.
     */
    public function resolve(ProductionOrder $production_order, Model $workstation, Process $process): ?array
    {
        $scope = [
            'process' => $process->id,
            'subprocess' => match (true) {
                $workstation instanceof Subprocess => $workstation->id,
                $workstation instanceof Operation => $workstation->subprocess()->first()?->id,
                default => null,
            },
            'operation' => $workstation instanceof Operation ? $workstation->id : null,
        ];

        $reference = $this->referenceValues($production_order);

        $models = $this->getModelTypesFromRules();
        $key_by_class = collect($models)->mapWithKeys(fn ($config, $key) => [$config['model'] => $key])->all();

        $weights = json_decode(DB::table('items')->where('id', Rule::ITEM_ID)->value('settings') ?? '{}', true)['weights'] ?? [];

        // Reglas del proceso del puesto, con todas sus relaciones en una sola consulta
        $rule_ids = DB::table('model_has_subitems')
            ->where('model_type', Process::class)
            ->where('model_id', $process->id)
            ->pluck('subitem_id');

        $rules = Rule::whereIn('id', $rule_ids)->get();

        $relations = DB::table('model_has_subitems')
            ->whereIn('subitem_id', $rules->pluck('id'))
            ->get()
            ->groupBy('subitem_id');

        $best = null;

        foreach ($rules as $rule) {
            $values = [];
            foreach ($relations->get($rule->id, collect()) as $relation) {
                $key = $key_by_class[$relation->model_type] ?? null;
                if ($key) {
                    $values[$key][] = (int) $relation->model_id;
                }
            }

            // Alcance: proceso obligatorio; subproceso y operación solo si la regla los tiene
            $applies = true;
            foreach (['process', 'subprocess', 'operation'] as $key) {
                if (isset($values[$key]) && ! in_array((int) $scope[$key], $values[$key], true)) {
                    $applies = false;
                }
            }

            // Maestros: todos los que tenga la regla deben coincidir con la referencia
            $points = 0;
            $matched = [];
            foreach ($values as $key => $ids) {
                if (in_array($key, ['process', 'subprocess', 'operation'], true)) {
                    continue;
                }
                if (! isset($reference[$key]) || ! in_array((int) $reference[$key], $ids, true)) {
                    $applies = false;
                    break;
                }
                $points += (int) ($weights[$key] ?? 0);
                $matched[] = $key;
            }

            if (! $applies) {
                continue;
            }

            $depth = (isset($values['operation']) ? 2 : 0) + (isset($values['subprocess']) ? 1 : 0);

            if (! $best || $points > $best['points'] || ($points === $best['points'] && $depth > $best['depth'])) {
                $best = ['rule' => $rule, 'points' => $points, 'depth' => $depth, 'matched' => $matched, 'values' => $values];
            }
        }

        if ($best) {
            $best['criteria'] = $this->criteria($best['values'], $weights);
        }

        return $best;
    }

    /**
     * Criterios de una regla para mostrarlos: [['key', 'label', 'value', 'points']], primero el alcance
     * (proceso, subproceso, operación) y luego los maestros de mayor a menor peso.
     */
    protected function criteria(array $values, array $weights): array
    {
        $models = $this->getModelTypesFromRules();
        $scope = ['process', 'subprocess', 'operation'];

        return collect($values)
            ->sortBy(fn ($ids, $key) => in_array($key, $scope, true)
                ? array_search($key, $scope, true)
                : 1000 - (int) ($weights[$key] ?? 0))
            ->map(fn ($ids, $key) => [
                'key' => $key,
                'label' => $models[$key]['label'] ?? $key,
                'value' => $models[$key]['model']::withoutGlobalScopes()->whereIn('id', $ids)->pluck('name')->implode(', '),
                'points' => in_array($key, $scope, true) ? null : (int) ($weights[$key] ?? 0),
            ])
            ->values()
            ->all();
    }
}
