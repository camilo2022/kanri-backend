<?php

namespace App\Services;

use App\Models\Operation;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\Rule;
use App\Models\Subprocess;
use App\Traits\ModelTypes;
use Illuminate\Database\Eloquent\Model;
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
     * Valores de la referencia por maestro (clave de getModelTypesFromRules => id).
     */
    public function referenceValues(ProductionOrder $productionOrder): array
    {
        $sheet = $productionOrder->technical_sheet;
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
    public function resolve(ProductionOrder $productionOrder, Model $workstation, Process $process): ?array
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

        $reference = $this->referenceValues($productionOrder);

        $models = $this->getModelTypesFromRules();
        $keyByClass = collect($models)->mapWithKeys(fn ($config, $key) => [$config['model'] => $key])->all();

        $weights = json_decode(DB::table('items')->where('id', Rule::ITEM_ID)->value('settings') ?? '{}', true)['weights'] ?? [];

        // Reglas del proceso del puesto, con todas sus relaciones en una sola consulta
        $ruleIds = DB::table('model_has_subitems')
            ->where('model_type', Process::class)
            ->where('model_id', $process->id)
            ->pluck('subitem_id');

        $rules = Rule::whereIn('id', $ruleIds)->get();

        $relations = DB::table('model_has_subitems')
            ->whereIn('subitem_id', $rules->pluck('id'))
            ->get()
            ->groupBy('subitem_id');

        $best = null;

        foreach ($rules as $rule) {
            $values = [];
            foreach ($relations->get($rule->id, collect()) as $relation) {
                $key = $keyByClass[$relation->model_type] ?? null;
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
