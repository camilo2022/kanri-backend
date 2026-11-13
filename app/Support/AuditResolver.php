<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;

/**
 * Traduce lo que la auditoría guarda como número.
 *
 * `laravel-auditing` solo guarda el valor crudo: al cambiar una llave foránea queda `wash_tone_id: 1 → 2` y no hay forma
 * de saber qué nombre tenía cada número. Aquí se consulta el nombre actual de cada registro referenciado, en una sola
 * consulta por tabla y sin descartar los desactivados (un valor viejo suele apuntar a algo que ya se desactivó).
 *
 * Solo se usa al consultar las auditorías; no cambia lo que se guarda.
 */
class AuditResolver
{
    /** Campos cuyo nombre de modelo no sale de quitar `_id` (campo => clase). */
    private const FIELD_MODELS = [
        'user_id' => \App\Models\User::class,
        'input_size_id' => \App\Models\Size::class,
        'output_size_id' => \App\Models\Size::class,
        'workstation_id' => \App\Models\Subitem::class,
        'pattern_maker_id' => \App\Models\Employee::class,
    ];

    /** Modelos cuyo nombre legible no está en una columna propia. */
    private const CUSTOM_LABELS = [
        \App\Models\User::class,
        \App\Models\Employee::class,
        \App\Models\Person::class,
        \App\Models\ProductionOrder::class,
        \App\Models\TechnicalSheet::class,
    ];

    /** Modelos cuyo nombre legible está en otra columna distinta a la habitual. */
    private const LABEL_COLUMN_BY_MODEL = [
        \App\Models\Permission::class => 'title',
        \App\Models\Role::class => 'title',
    ];

    /** Nombre en español por modelo (clase corta). Es el que ve la pantalla en «Sección modificada». */
    public const MODEL_NAMES = [
        'AccountType' => 'Tipo de Cuenta',
        'Area' => 'Área',
        'BackType' => 'Tipo de Trasero',
        'Bank' => 'Banco',
        'BankAccount' => 'Cuenta Bancaria',
        'BloodType' => 'Tipo de Sangre',
        'BootType' => 'Tipo de Bota',
        'Category' => 'Categoría',
        'ClothingLine' => 'Linea',
        'Collection' => 'Colección',
        'Color' => 'Color',
        'CompensationFund' => 'Caja de Compensación',
        'DocumentType' => 'Tipo de Documento',
        'Employee' => 'Empleado',
        'File' => 'Archivo',
        'FileSubtype' => 'Subtipo de Archivo',
        'FileType' => 'Tipo de Archivo',
        'GarmentType' => 'Tipo de Prenda',
        'Gender' => 'Género',
        'Group' => 'Grupo',
        'HealthEntity' => 'Entidad de Salud',
        'Module' => 'Módulo',
        'Movement' => 'Movimiento',
        'MovementDetail' => 'Detalle de Movimiento',
        'MovementDetailQuantity' => 'Cantidades de Detalle de Movimiento',
        'MovementDetailRedistribution' => 'Redistribución de Movimiento',
        'Operation' => 'Operación',
        'PensionFund' => 'Fondo de Pensión',
        'Permission' => 'Permiso',
        'Person' => 'Persona',
        'PersonType' => 'Tipo de Persona',
        'Piece' => 'Pieza',
        'Position' => 'Cargo',
        'Process' => 'Proceso',
        'Product' => 'Producto',
        'ProductionOrder' => 'Orden de Producción',
        'ProductionOrderDetail' => 'Detalle de Orden de Producción',
        'ProductionOrderDetailQuantities' => 'Cantidades de Detalle de Orden de Producción',
        'Productivity' => 'Productividad',
        'ProductivityDetail' => 'Registro de Trabajo',
        'RiskManager' => 'Administradora de Riesgo',
        'Role' => 'Rol',
        'Rule' => 'Regla',
        'Size' => 'Talla',
        'Stop' => 'Parada',
        'StopType' => 'Tipo de Parada',
        'Subcategory' => 'Subcategoría',
        'Subgroup' => 'Subgrupo',
        'Submodule' => 'Submódulo',
        'Subprocess' => 'Subproceso',
        'Supplier' => 'Proveedor',
        'SupplierType' => 'Tipo de Proveedor',
        'Supply' => 'Insumo',
        'SupplyType' => 'Tipo de Insumo',
        'TechnicalSheet' => 'Ficha Técnica',
        'TechnicalSheetDetail' => 'Detalle de Ficha Técnica',
        'Trademark' => 'Marca',
        'Typology' => 'Tipología',
        'User' => 'Usuario',
        'Variant' => 'Variante',
        'WaistbandType' => 'Tipo de Pretina',
        'WashTone' => 'Tono de Lavado',
        'YokeType' => 'Tipo de Canesú',
    ];

    /** Columnas que sirven de nombre legible, en orden de preferencia. */
    private const LABEL_COLUMNS = ['name', 'names', 'title', 'code', 'consecutive'];

    /** @var array<string, array{0: class-string<Model>, 1: string}|null> */
    private static array $fieldCache = [];

    /** Eventos que el historial muestra: editar, asociar o sincronizar sin valores no dice qué pasó. */
    public static function withChanges($query)
    {
        return $query->where(fn ($events) => $events->whereNotIn('event', ['updated', 'attach', 'detach', 'sync'])
            ->orWhereRaw("COALESCE(old_values, '[]') NOT IN ('[]', '{}')")
            ->orWhereRaw("COALESCE(new_values, '[]') NOT IN ('[]', '{}')"));
    }

    /**
     * Registros del modelo que tienen algo que mostrar en su historial, en una consulta: [id => true].
     *
     * @return array<int, true>
     */
    public static function auditedIds(string $class, array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return self::withChanges(Audit::query()->where('auditable_type', $class)->whereIn('auditable_id', $ids))
            ->distinct()->pluck('auditable_id')->mapWithKeys(fn ($id) => [$id => true])->all();
    }

    /**
     * Devuelve, por cada auditoría, los nombres de los números que aparecen en sus valores:
     * [audit_id => [campo => [id => nombre]]].
     *
     * @param  Collection<int, \OwenIt\Auditing\Models\Audit>  $audits
     */
    public static function forAudits(Collection $audits): array
    {
        $idsByField = [];
        foreach ($audits as $audit) {
            foreach (self::foreignValues($audit) as $field => $ids) {
                $idsByField[$field] = array_unique(array_merge($idsByField[$field] ?? [], $ids));
            }
        }

        $labels = [];
        foreach ($idsByField as $field => $ids) {
            [$class, $column] = self::resolveField($field);
            $labels[$field] = self::labelsFor($class, $column, $ids);
        }

        $result = [];
        foreach ($audits as $audit) {
            $own = [];
            foreach (self::foreignValues($audit) as $field => $ids) {
                $found = array_intersect_key($labels[$field] ?? [], array_flip($ids));
                if ($found) {
                    $own[$field] = $found;
                }
            }
            $result[$audit->id] = $own;
        }

        return $result;
    }

    /** @return array<string, int[]> campo => ids que ese registro menciona (antes y después) */
    private static function foreignValues($audit): array
    {
        $found = [];
        foreach ([$audit->old_values, $audit->new_values] as $values) {
            foreach ((array) $values as $field => $value) {
                if (is_string($field) && str_ends_with($field, '_id') && is_numeric($value) && self::resolveField($field)) {
                    $found[$field][] = (int) $value;
                }
            }
        }

        return array_map('array_unique', $found);
    }

    /** @return array{0: class-string<Model>, 1: string}|null modelo y columna que sirve de nombre */
    private static function resolveField(string $field): ?array
    {
        if (array_key_exists($field, self::$fieldCache)) {
            return self::$fieldCache[$field];
        }

        $class = self::FIELD_MODELS[$field] ?? 'App\\Models\\' . Str::studly(Str::beforeLast($field, '_id'));
        $resolved = null;

        if (is_string($class) && class_exists($class) && is_subclass_of($class, Model::class)) {
            $column = self::labelColumn($class);
            $resolved = $column ? [$class, $column] : null;
        }

        return self::$fieldCache[$field] = $resolved;
    }

    /** Columna que sirve de nombre; `custom` si el nombre se arma con relaciones. */
    private static function labelColumn(string $class): ?string
    {
        if (in_array($class, self::CUSTOM_LABELS, true)) {
            return 'custom';
        }
        if (isset(self::LABEL_COLUMN_BY_MODEL[$class])) {
            return self::LABEL_COLUMN_BY_MODEL[$class];
        }
        $table = (new $class)->getTable();

        return collect(self::LABEL_COLUMNS)->first(fn ($c) => Schema::hasColumn($table, $c));
    }

    /** Consulta del modelo sin descartar los desactivados. */
    public static function queryOf(string $class)
    {
        $query = $class::query();

        return in_array(SoftDeletes::class, class_uses_recursive($class), true) ? $query->withTrashed() : $query;
    }

    /**
     * Nombre legible de los registros de un modelo: [id => nombre]. Los detalles sin nombre propio toman el del registro
     * al que apuntan (su `model_id`) o el de su talla.
     *
     * @return array<int, string>
     */
    public static function labelsOf(string $class, array $ids): array
    {
        if (! $ids || ! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return [];
        }
        $column = self::labelColumn($class);
        if ($column) {
            return self::labelsFor($class, $column, $ids);
        }

        $labels = [];
        foreach (self::queryOf($class)->whereIn('id', $ids)->get() as $row) {
            if ($row->model_type && $row->model_id) {
                $labels[$row->id] = self::labelsOf($row->model_type, [$row->model_id])[$row->model_id] ?? null;
            } elseif ($row->size_id) {
                $labels[$row->id] = self::labelsOf(\App\Models\Size::class, [$row->size_id])[$row->size_id] ?? null;
            }
        }

        return array_filter($labels);
    }

    /** @return array<int, string> id => nombre */
    private static function labelsFor(string $class, string $column, array $ids): array
    {
        $query = self::queryOf($class);

        if ($column !== 'custom') {
            return $query->whereIn('id', $ids)->pluck($column, 'id')->map(fn ($v) => (string) $v)->all();
        }

        $full_name = fn ($person) => $person ? trim($person->names . ' ' . $person->last_names) : null;

        return match ($class) {
            \App\Models\User::class => $query->with(['employee' => ['person']])->whereIn('id', $ids)->get()
                ->mapWithKeys(fn ($user) => [$user->id => $full_name($user->employee?->person) ?? $user->email])->all(),
            \App\Models\Employee::class => $query->with(['person'])->whereIn('id', $ids)->get()
                ->mapWithKeys(fn ($employee) => [$employee->id => $full_name($employee->person) ?? "Empleado #{$employee->id}"])->all(),
            \App\Models\Person::class => $query->whereIn('id', $ids)->get()
                ->mapWithKeys(fn ($person) => [$person->id => $full_name($person)])->all(),
            \App\Models\ProductionOrder::class => $query->with(['technical_sheet' => ['product']])->whereIn('id', $ids)->get()
                ->mapWithKeys(fn ($order) => [$order->id => trim(($order->technical_sheet?->product?->code ?? $order->consecutive) . '-' . $order->cut, '-')])->all(),
            \App\Models\TechnicalSheet::class => $query->with(['product'])->whereIn('id', $ids)->get()
                ->mapWithKeys(fn ($sheet) => [$sheet->id => $sheet->product?->code ?? $sheet->code ?? $sheet->consecutive ?? "Ficha #{$sheet->id}"])->all(),
        };
    }
}
