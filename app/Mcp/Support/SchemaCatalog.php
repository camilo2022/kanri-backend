<?php

namespace App\Mcp\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Qué tablas y columnas de Kanri puede consultar una persona por SQL. Las reglas están en config/assistant_tables.php:
 * el permiso de una tabla es el de la ruta `all` que la lista, los catálogos (subitems) se reparten por item_id y hay
 * tablas y columnas que nunca se leen.
 */
class SchemaCatalog
{
    public function isForbidden(string $table): bool
    {
        return in_array($table, config('assistant_tables.forbidden', []), true);
    }

    public function isKnown(string $table): bool
    {
        return $table === config('assistant_tables.catalog_table')
            || $table === config('assistant_tables.catalog_items_table')
            || isset(config('assistant_tables.tables', [])[$table]);
    }

    /**
     * Permisos que abren una tabla normal (basta uno).
     *
     * @return array<int, string>
     */
    public function permissions(string $table): array
    {
        return config('assistant_tables.tables', [])[$table] ?? [];
    }

    /**
     * Catálogos (item_id => nombre) que la persona puede leer.
     *
     * @return array<int, string>
     */
    public function allowedCatalogs(User $actor): array
    {
        $allowed = [];

        foreach (config('assistant_tables.catalogs', []) as $item_id => $catalog) {
            if ($catalog['permissions'] && $actor->getDirectPermissions()->whereIn('name', $catalog['permissions'])->isNotEmpty()) {
                $allowed[$item_id] = $catalog['name'];
            }
        }

        return $allowed;
    }

    public function canRead(User $actor, string $table): bool
    {
        if ($this->isForbidden($table) || ! $this->isKnown($table)) {
            return false;
        }

        if ($table === config('assistant_tables.catalog_table') || $table === config('assistant_tables.catalog_items_table')) {
            return $this->allowedCatalogs($actor) !== [];
        }

        return $actor->getDirectPermissions()->whereIn('name', $this->permissions($table))->isNotEmpty();
    }

    /**
     * Tablas que la persona puede consultar, con su número aproximado de filas.
     *
     * @return array<string, int>
     */
    public function readableTables(User $actor): array
    {
        $tables = array_merge(
            array_keys(config('assistant_tables.tables', [])),
            [config('assistant_tables.catalog_table'), config('assistant_tables.catalog_items_table')]
        );

        sort($tables);

        $rows = $this->rows();
        $readable = [];

        foreach ($tables as $table) {
            if ($this->canRead($actor, $table)) {
                $readable[$table] = $rows[$table] ?? 0;
            }
        }

        return $readable;
    }

    /**
     * Columnas de la tabla que se pueden leer (sin las ocultas), en su orden.
     *
     * @return array<int, array{name: string, type: string, nullable: bool}>
     */
    public function columns(string $table): array
    {
        $hidden = config('assistant_tables.hidden_columns.'.$table, []);

        return array_values(array_filter(
            $this->allColumns($table),
            fn (array $column) => ! in_array($column['name'], $hidden, true)
        ));
    }

    public function hasColumn(string $table, string $column): bool
    {
        return collect($this->allColumns($table))->contains('name', $column);
    }

    private function allColumns(string $table): array
    {
        return Cache::remember('assistant_columns:'.$table, now()->addHour(), function () use ($table) {
            return collect(DB::select(
                'SELECT COLUMN_NAME AS name, COLUMN_TYPE AS type, IS_NULLABLE AS nullable FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
                [$table]
            ))->map(fn ($column) => ['name' => $column->name, 'type' => $column->type, 'nullable' => $column->nullable === 'YES'])->all();
        });
    }

    /**
     * @return array<string, int>
     */
    private function rows(): array
    {
        return Cache::remember('assistant_table_rows', now()->addHour(), function () {
            return collect(DB::select('SELECT TABLE_NAME AS name, TABLE_ROWS AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'))
                ->mapWithKeys(fn ($table) => [$table->name => (int) $table->total])->all();
        });
    }
}
