<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Convierte los valores de una auditoría en lo que lee una persona: el nombre de cada campo sale del comentario de su
 * columna y los valores se traducen (identificadores, fechas, sí o no). Lo que no es columna (relaciones y datos dentro
 * de `settings`) no tiene comentario, por eso sus nombres están aquí.
 *
 * Solo se usa al consultar las auditorías; no cambia lo que se guarda.
 */
class AuditChanges
{
    private const UNNAMED = 'Dato sin nombre';

    private const CHANGED = 'Modificada';

    private const SECRET = 'Cambiada';

    /** No le dicen nada a quien revisa el historial. */
    private const HIDDEN = ['uuid', 'item_id', 'guard_name', 'metadata', 'model_type', 'remember_token'];

    private const SECRETS = ['password', 'remember_token'];

    /** Campos que no son columna de su tabla: relaciones que se asocian o sincronizan. */
    public const FIELD_LABELS = [
        'after_processes' => 'Procesos siguientes',
        'after_subprocesses' => 'Subprocesos siguientes',
        'date' => 'Fecha',
        'document_type' => 'Tipo de documento',
        'fabric_id' => 'Tela',
        'file_type' => 'Tipo de archivo',
        'group' => 'Grupo',
        'model_id' => 'Registro asociado',
        'permissions' => 'Permisos',
        'person_type' => 'Tipo de persona',
        'pieces' => 'Piezas',
        'process' => 'Proceso',
        'processes' => 'Procesos',
        'roles' => 'Roles',
        'rolls' => 'Rollos',
        'settings' => 'Configuraciones',
        'sizes' => 'Tallas',
        'operations' => 'Operaciones',
        'subprocesses' => 'Subprocesos',
        'stop_type' => 'Tipo de parada',
        'subprocess' => 'Subproceso',
        'supplier_type' => 'Tipo de proveedor',
        'supplies' => 'Insumos',
        'supply_type' => 'Tipo de insumo',
        'technical_sheet_relation' => 'Ficha técnica relacionada',
        'technical_sheets' => 'Fichas técnicas',
        'typology' => 'Tipología',
        'variants' => 'Variantes',
    ];

    /** Datos dentro de `settings`. */
    public const SETTINGS_LABELS = [
        'any_workstation' => 'Cualquier puesto de trabajo',
        'available_minutes' => 'Minutos disponibles',
        'base_time' => 'Tiempo base',
        'capacities' => 'Capacidades',
        'capacity' => 'Capacidad',
        'code' => 'Código',
        'color' => 'Color',
        'current_run' => 'Corrida actual',
        'curve_row' => 'Fila de la curva',
        'date' => 'Fecha',
        'dead_minutes' => 'Minutos muertos',
        'duration_minutes' => 'Duración',
        'efficiency' => 'Eficiencia',
        'end' => 'Fin',
        'end_date' => 'Fecha de fin',
        'finished_at' => 'Hora de finalización',
        'finished_product' => 'Producto terminado',
        'goal_hour' => 'Meta por hora',
        'has_account_bank' => 'Tiene cuenta bancaria',
        'has_data_bank' => 'Pide datos bancarios',
        'has_data_person' => 'Pide datos de persona',
        'has_defined_time_range' => 'Tiene rango de tiempo definido',
        'has_person' => 'Tiene persona',
        'has_supplies' => 'Tiene insumos',
        'has_variants' => 'Tiene variantes',
        'heigth' => 'Alto',
        'in_production_order' => 'Pertenece a la orden de producción',
        'in_productivity' => 'Mide productividad',
        'in_technical_sheet' => 'Pertenece a la ficha técnica',
        'meters' => 'Metros',
        'minutes' => 'Minutos',
        'next_conditions' => 'Condiciones para continuar',
        'only_after_previous' => 'Solo después del anterior',
        'operation_flow' => 'Orden de las operaciones',
        'operations' => 'Operaciones',
        'paragraph' => 'Texto explicativo',
        'people' => 'Personas',
        'priority_checks' => 'Controles de prioridad',
        'produced' => 'Producidas',
        'productivity_measurement' => 'Medición de productividad',
        'quantity' => 'Cantidad',
        'range_time' => 'Rango de tiempo',
        'reprocesses' => 'Reprocesos',
        'rolls' => 'Rollos',
        'rule_id' => 'Regla',
        'sale' => 'Venta',
        'scanned_at' => 'Hora de escaneo',
        'schedule_units' => 'Unidades al programar',
        'scheduled' => 'Programación',
        'simultaneous_operations' => 'Operaciones simultáneas',
        'standard_minutes' => 'Minutos estándar',
        'start' => 'Inicio',
        'start_date' => 'Fecha de inicio',
        'stop_minutes' => 'Minutos de parada',
        'stops' => 'Paradas',
        'subprocess_flow' => 'Orden de los subprocesos',
        'subprocesses' => 'Subprocesos',
        'supply' => 'Insumo',
        'time' => 'Tiempo',
        'time_multiplier' => 'Cantidad que se digita al programar',
        'time_unit' => 'Unidad de tiempo',
        'unit_time' => 'Tiempo por unidad',
        'units' => 'Unidades',
        'units_entry' => 'Registro de unidades',
        'validations' => 'Validaciones',
        'values' => 'Valores',
        'width' => 'Ancho',
        'work_in_progress' => 'Trabajo en proceso',
        'worked_minutes' => 'Minutos trabajados',
        'workstation' => 'Puesto de trabajo',
    ];

    /** Datos de `settings` que son la estructura interna de una tabla o formulario: solo se avisa que cambiaron. */
    private const STRUCTURE_LABELS = [
        'dinamic' => 'Tabla de la ficha',
        'form' => 'Formulario',
        'schema' => 'Tabla de la ficha',
        'static' => 'Tabla de la ficha',
    ];

    private const HIDDEN_SETTINGS = ['uuid', 'units_at'];

    /** @var array<string, array<string, array{comment: string, type: string}>> */
    private static array $columns = [];

    /**
     * Valores anteriores y nuevos con el nombre legible de cada campo: ['old' => [nombre => valor], 'new' => [...]].
     *
     * @param  array  $resolved  nombres de sus llaves foráneas (AuditResolver::forAudits): [campo => [id => nombre]]
     */
    public static function of($audit, array $resolved): array
    {
        $table = class_exists($audit->auditable_type) ? (new $audit->auditable_type)->getTable() : null;
        $old = (array) $audit->old_values;
        $new = (array) $audit->new_values;
        $changes = ['old' => [], 'new' => []];

        foreach (array_keys($old + $new) as $field) {
            if (in_array($field, self::HIDDEN, true) && ! in_array($field, self::SECRETS, true)) {
                continue;
            }
            if ($field === 'settings') {
                self::settings($old[$field] ?? null, $new[$field] ?? null, $changes);

                continue;
            }
            if (in_array($field, self::SECRETS, true)) {
                if ($field === 'password') {
                    $changes['new'][self::label($table, $field)] = self::SECRET;
                }

                continue;
            }
            $type = $new['model_type'] ?? $old['model_type'] ?? null;
            $label = self::label($table, $field);
            foreach (['old' => $old, 'new' => $new] as $side => $values) {
                if (array_key_exists($field, $values)) {
                    $changes[$side][$label] = self::value($table, $field, $values[$field], $resolved, $type);
                }
            }
        }

        return $changes;
    }

    /** Unidades que llevaba el registro de trabajo en esos valores: suma de `settings.produced`. */
    public static function producedUnits($values): int
    {
        return (int) array_sum((array) (self::decode(((array) $values)['settings'] ?? null)['produced'] ?? []));
    }

    /** Oculta los valores secretos en una respuesta que entrega los valores crudos. */
    public static function maskSecrets($values)
    {
        if (! is_array($values)) {
            return $values;
        }
        foreach (self::SECRETS as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = self::SECRET;
            }
        }

        return $values;
    }

    public static function label(?string $table, string $field): string
    {
        $comment = $table ? (self::columns($table)[$field]['comment'] ?? null) : null;

        return $comment ?: (self::FIELD_LABELS[$field] ?? self::UNNAMED);
    }

    /** @return array<string, array{comment: string, type: string}> */
    private static function columns(string $table): array
    {
        return self::$columns[$table] ??= collect(Schema::getColumns($table))
            ->mapWithKeys(fn ($column) => [$column['name'] => ['comment' => (string) $column['comment'], 'type' => $column['type_name']]])
            ->all();
    }

    private static function value(?string $table, string $field, $value, array $resolved, $type)
    {
        if ($value === null || $value === '') {
            return $value;
        }
        if (is_array($value)) {
            return self::relationNames($value);
        }
        if ($field === 'model_id') {
            return self::morph($value, $type);
        }
        if (str_ends_with($field, '_id') && is_numeric($value)) {
            $name = $resolved[$field][(int) $value] ?? null;

            return $name ? "{$name} (#{$value})" : "#{$value}";
        }
        if ($table && (self::columns($table)[$field]['type'] ?? null) === 'tinyint') {
            return $value ? 'Sí' : 'No';
        }

        return self::scalar($value);
    }

    private static function scalar($value)
    {
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        if (! is_string($value)) {
            return $value;
        }
        if (str_starts_with($value, 'App\\Models\\')) {
            return AuditResolver::MODEL_NAMES[class_basename($value)] ?? self::UNNAMED;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || preg_match('/^\d{4}-\d{2}-\d{2} 00:00:00$/', $value)) {
            return Carbon::parse($value)->format('d/m/Y');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $value)) {
            return Carbon::parse($value)->setTimezone(config('app.timezone'))->format('d/m/Y h:i A');
        }
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return Carbon::parse($value)->format('h:i A');
        }

        return $value;
    }

    private static function morph($value, $type): string
    {
        if (! is_string($type)) {
            return "#{$value}";
        }
        $name = AuditResolver::labelsOf($type, [(int) $value])[(int) $value] ?? null;
        $kind = AuditResolver::MODEL_NAMES[class_basename($type)] ?? null;

        return trim(($kind ? "{$kind}: " : '') . ($name ? "{$name} (#{$value})" : "#{$value}"));
    }

    /** Los registros asociados llegan como lista de objetos: se muestra el nombre de cada uno. */
    private static function relationNames(array $list): array
    {
        return array_values(array_map(function ($item) {
            if (! is_array($item)) {
                return is_numeric($item) ? "#{$item}" : $item;
            }

            return $item['title'] ?? $item['name'] ?? $item['names'] ?? $item['code'] ?? $item['consecutive'] ?? (isset($item['id']) ? "#{$item['id']}" : self::UNNAMED);
        }, $list));
    }

    /** Solo lo que cambió dentro de `settings`, cada dato con su nombre. */
    private static function settings($old, $new, array &$changes): void
    {
        $old = self::decode($old);
        $new = self::decode($new);

        foreach (array_keys($old + $new) as $key) {
            $before = $old[$key] ?? null;
            $after = $new[$key] ?? null;
            if (! is_string($key) || in_array($key, self::HIDDEN_SETTINGS, true) || $before == $after) {
                continue;
            }
            if (isset(self::STRUCTURE_LABELS[$key])) {
                $changes['new'][self::STRUCTURE_LABELS[$key]] = self::CHANGED;

                continue;
            }
            $label = self::SETTINGS_LABELS[$key] ?? self::UNNAMED;
            if (self::isFlat($before) && self::isFlat($after)) {
                foreach (array_keys((array) $before + (array) $after) as $sub_key) {
                    $old_value = $before[$sub_key] ?? null;
                    $new_value = $after[$sub_key] ?? null;
                    if ($old_value == $new_value) {
                        continue;
                    }
                    $name = "{$label}: " . Str::lower(self::SETTINGS_LABELS[$sub_key] ?? self::UNNAMED);
                    self::setting($name, $old_value, $new_value, $changes);
                }

                continue;
            }
            if (is_array($before) || is_array($after)) {
                $changes['new'][$label] = self::CHANGED;

                continue;
            }
            self::setting($label, $before, $after, $changes, $key);
        }
    }

    private static function setting(string $label, $before, $after, array &$changes, ?string $key = null): void
    {
        $format = function ($value) use ($key) {
            if ($key && str_ends_with($key, '_id') && is_numeric($value)) {
                $class = 'App\\Models\\' . Str::studly(Str::beforeLast($key, '_id'));
                $name = AuditResolver::labelsOf($class, [(int) $value])[(int) $value] ?? null;

                return $name ? "{$name} (#{$value})" : "#{$value}";
            }

            return is_array($value) ? self::CHANGED : self::scalar($value);
        };
        if ($before !== null) {
            $changes['old'][$label] = $format($before);
        }
        if ($after !== null) {
            $changes['new'][$label] = $format($after);
        }
    }

    /** Objeto de un nivel con todos sus datos conocidos, como `scheduled`. */
    private static function isFlat($value): bool
    {
        if ($value === null) {
            return true;
        }
        if (! is_array($value) || ! Arr::isAssoc($value)) {
            return false;
        }
        foreach ($value as $sub_key => $sub_value) {
            if (is_array($sub_value) || ! isset(self::SETTINGS_LABELS[$sub_key])) {
                return false;
            }
        }

        return true;
    }

    private static function decode($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }
}
