<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ReflectionMethod;

/**
 * Ordenar una lista por una columna de la tabla del frontend, aunque el dato viva en otra tabla.
 *
 * Cada modelo declara en `sortable_columns()` la clave que manda el frontend (`column`) y la ruta del dato:
 *
 *   'id'           => 'id'                                                   columna propia
 *   'trademark'    => 'production_order_detail.…product.trademark.name'     relaciones separadas por punto y, al final, la columna
 *   'process'      => 'model:Process.name'                                   MorphTo: se indica el tipo con `relación:Clase`
 *   'color_id'     => 'related:Color.name'                                   relación que recibe la clase (Supply::related($model))
 *   'width'        => 'settings->values->width'                              dato dentro de una columna JSON
 *   'detail_955'   => 'technical_sheet.technical_sheet_details[model_type=App\Models\Process,model_id=955].status'
 *                                                                            filtros de un salto entre corchetes (columna=valor, separados por coma)
 *   'reference'    => ['…product.code', 'production_order_detail.…cut']      varias rutas: se ordena por la primera y desempata con las siguientes
 *
 * Relaciones que se pueden recorrer: BelongsTo, HasOne/HasMany, MorphTo (con tipo) y MorphToMany (directa o inversa). Se
 * resuelve en SQL con un subquery por relación, de la tabla de la lista hacia afuera, y no carga relaciones en PHP. Cada
 * tabla del subquery lleva su propio alias (sort_1, sort_2…): muchos modelos comparten tabla (subitems) y, sin alias, la
 * tabla de la relación se confundiría con la de la lista. Si la relación trae varias filas se ordena por la menor (MIN).
 */
trait SortableByRelation
{
    abstract public static function sortable_columns(): array;

    public function scopeSort(Builder $query, ?string $column, ?string $dir): Builder
    {
        $paths = static::sortable_columns()[$column] ?? null;

        if (! $paths) {
            return $query;
        }

        $dir = strtolower((string) $dir) === 'desc' ? 'desc' : 'asc';

        foreach ((array) $paths as $path) {
            if (str_contains($path, '->')) {
                [$json_column, $json_path] = explode('->', $path, 2);
                $value = 'json_extract('.$query->getQuery()->getGrammar()->wrap($this->qualifyColumn($json_column)).', ?)';
                $bindings = ['$.'.str_replace('->', '.', $json_path)];

                // JSON_EXTRACT sin JSON_UNQUOTE: MySQL compara los números como números y los textos alfabéticamente
                $query->orderByRaw($value.' is null', $bindings)
                    ->orderByRaw($value.' '.$dir, $bindings);

                continue;
            }

            if (! str_contains($path, '.')) {
                $query->orderBy($this->qualifyColumn($path), $dir);

                continue;
            }

            $subquery = $this->relationSortSubquery($path);

            $query->orderByRaw('('.$subquery->toSql().') is null', $subquery->getBindings())
                ->orderBy($subquery, $dir);
        }

        // Desempate por la llave primaria: sin él, las filas con el mismo valor pueden repetirse o saltarse al paginar
        if (! in_array($this->getKeyName(), (array) $paths, true)) {
            $query->orderBy($this->qualifyColumn($this->getKeyName()), $dir);
        }

        return $query;
    }

    protected function relationSortSubquery(string $path): Builder
    {
        $segments = explode('.', $path);
        $column = array_pop($segments);

        $parent = $this;
        $parent_table = $this->getTable();
        $subquery = null;

        foreach ($segments as $hop => $segment) {
            $filters = [];
            if (preg_match('/^(.+)\[(.+)\]$/', $segment, $match)) {
                $segment = $match[1];
                foreach (explode(',', $match[2]) as $filter) {
                    [$filter_column, $filter_value] = array_pad(explode('=', $filter, 2), 2, null);
                    $filters[trim($filter_column)] = trim((string) $filter_value);
                }
            }

            [$name, $type] = array_pad(explode(':', $segment, 2), 2, null);

            if (! method_exists($parent, $name)) {
                throw new InvalidArgumentException("La relación [{$name}] no existe en ".$parent::class);
            }

            $class = $type ? $this->sortClass($type) : null;
            $relation = $class && (new ReflectionMethod($parent, $name))->getNumberOfParameters() > 0
                ? $parent->{$name}($class)
                : $parent->{$name}();
            $related = $this->sortRelatedModel($relation, $name, $class, $parent);
            $alias = 'sort_'.($hop + 1);

            // El modelo toma el alias como tabla para que sus scopes globales (borrados) apunten al alias
            $query = (clone $related)->setTable($alias)->newQuery()
                ->withoutGlobalScopes(method_exists($relation, 'getQuery') ? $relation->getQuery()->removedScopes() : [])
                ->from($related->getTable(), $alias);

            $this->linkSortRelation($query, $subquery, $relation, $parent_table, $alias, $related);

            foreach ($filters as $filter_column => $filter_value) {
                $query->where($alias.'.'.$filter_column, $filter_value);
            }

            $subquery = $query;
            $parent = $related;
            $parent_table = $alias;
        }

        return $subquery->selectRaw('min('.$subquery->getQuery()->getGrammar()->wrap($parent_table.'.'.$column).')');
    }

    protected function sortClass(string $type): string
    {
        $class = str_contains($type, '\\') ? $type : 'App\\Models\\'.$type;

        if (! class_exists($class)) {
            throw new InvalidArgumentException("La clase [{$class}] no existe.");
        }

        return $class;
    }

    protected function sortRelatedModel($relation, string $name, ?string $class, Model $parent): Model
    {
        if (! $relation instanceof MorphTo) {
            return $relation->getRelated();
        }

        if (! $class) {
            throw new InvalidArgumentException("La relación polimórfica [{$name}] de ".$parent::class.' necesita el tipo: [nombre:Clase].');
        }

        return new $class;
    }

    /**
     * Une la tabla relacionada (su alias) con la anterior: con la tabla de la lista (correlacionado) si es el primer salto,
     * o con el subquery del salto anterior (que se limita a la columna que enlaza).
     */
    protected function linkSortRelation(Builder $query, ?Builder $previous, $relation, string $parent_table, string $alias, Model $related): void
    {
        if ($relation instanceof MorphTo) {
            $key = $alias.'.'.$related->getKeyName();
            $foreign_key = $parent_table.'.'.$relation->getForeignKeyName();
            $morph_type = $parent_table.'.'.$relation->getMorphType();
            $morph_class = $related->getMorphClass();

            if ($previous === null) {
                $query->whereColumn($key, $foreign_key)
                    ->whereRaw($query->getQuery()->getGrammar()->wrap($morph_type).' = ?', [$morph_class]);

                return;
            }

            $previous->where($morph_type, $morph_class)->select($foreign_key);
            $query->whereIn($key, $previous);

            return;
        }

        if ($relation instanceof BelongsTo) {
            $owner_key = $alias.'.'.$relation->getOwnerKeyName();
            $foreign_key = $parent_table.'.'.$relation->getForeignKeyName();

            if ($previous === null) {
                $query->whereColumn($owner_key, $foreign_key);

                return;
            }

            $previous->select($foreign_key);
            $query->whereIn($owner_key, $previous);

            return;
        }

        if ($relation instanceof HasOneOrMany) {
            $foreign_key = $alias.'.'.$relation->getForeignKeyName();
            $parent_key = $parent_table.'.'.$relation->getLocalKeyName();

            if ($previous === null) {
                $query->whereColumn($foreign_key, $parent_key);

                return;
            }

            $previous->select($parent_key);
            $query->whereIn($foreign_key, $previous);

            return;
        }

        if ($relation instanceof MorphToMany) {
            $pivot = $relation->getTable();
            $parent_key = $parent_table.'.'.$relation->getParentKeyName();

            $ids = DB::table($pivot)
                ->select($pivot.'.'.$relation->getRelatedPivotKeyName())
                ->where($pivot.'.'.$relation->getMorphType(), $relation->getMorphClass());

            if ($previous === null) {
                $ids->whereColumn($pivot.'.'.$relation->getForeignPivotKeyName(), $parent_key);
            } else {
                $previous->select($parent_key);
                $ids->whereIn($pivot.'.'.$relation->getForeignPivotKeyName(), $previous);
            }

            $query->whereIn($alias.'.'.$relation->getRelatedKeyName(), $ids);

            return;
        }

        throw new InvalidArgumentException('La relación ['.$relation::class.'] no se puede usar para ordenar.');
    }
}
