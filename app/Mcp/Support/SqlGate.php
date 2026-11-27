<?php

namespace App\Mcp\Support;

use App\Mcp\McpFailure;
use App\Models\Permission;
use App\Models\User;

/**
 * Puerta de las consultas SQL del asistente. Recibe el SQL que escribió la IA y lo deja listo para ejecutarse solo si es
 * seguro y la persona tiene permiso:
 *
 * 1. Lo separa en piezas (palabras, textos, símbolos), sin interpretar nada de lo que hay dentro de los textos.
 * 2. Solo acepta una sentencia SELECT, sin comentarios, variables ni palabras peligrosas.
 * 3. Cada tabla que usa debe existir en config/assistant_tables.php y la persona debe tener su permiso.
 * 4. Cada tabla se reemplaza por una consulta con solo las columnas permitidas y las filas que la persona puede ver
 *    (sin registros eliminados, y los catálogos solo con los item_id que su permiso abre). Así, aunque el SQL de la IA
 *    intente otra cosa (OR 1=1, subconsultas), solo alcanza lo permitido.
 *
 * Además, la conexión que ejecuta el SQL es de un usuario de base de datos que solo puede leer.
 */
class SqlGate
{
    private const FORBIDDEN_WORDS = [
        'INTO', 'OUTFILE', 'DUMPFILE', 'LOAD_FILE', 'SLEEP', 'BENCHMARK', 'GET_LOCK', 'RELEASE_LOCK', 'IS_FREE_LOCK',
        'IS_USED_LOCK', 'LOCK', 'PROCEDURE', 'HANDLER', 'MASTER_POS_WAIT', 'WAIT_FOR_EXECUTED_GTID_SET', 'USE', 'FORCE',
        'IGNORE', 'FOR', 'WITH', 'STRAIGHT_JOIN', 'TABLE', 'VALUES',
    ];

    private const CLAUSE_ENDS = ['WHERE', 'GROUP', 'HAVING', 'ORDER', 'LIMIT', 'UNION', 'EXCEPT', 'INTERSECT', 'WINDOW'];

    private const NOT_ALIAS = [
        'WHERE', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'CROSS', 'NATURAL', 'STRAIGHT_JOIN', 'ON', 'USING', 'GROUP', 'ORDER',
        'LIMIT', 'HAVING', 'UNION', 'EXCEPT', 'INTERSECT', 'WINDOW', 'PARTITION', 'OUTER',
    ];

    public function __construct(private SchemaCatalog $schema) {}

    /**
     * @return array{sql: string, tables: array<int, string>, catalogs: array<int, string>|null}
     */
    public function prepare(User $actor, string $sql, bool $include_deleted = false): array
    {
        $tokens = $this->tokenize(trim($sql));

        if ($tokens && end($tokens)['v'] === ';' && end($tokens)['t'] === 'sym') {
            array_pop($tokens);
        }

        $this->check($tokens);

        $references = $this->references($tokens);
        $tables = array_values(array_unique(array_map(fn (array $reference) => $reference['table'], $references)));

        if (! $references) {
            throw McpFailure::invalidSql('La consulta debe leer de al menos una tabla con FROM.');
        }

        $catalogs = null;

        foreach ($tables as $table) {
            $this->authorize($actor, $table);
        }

        if (in_array(config('assistant_tables.catalog_table'), $tables, true)) {
            $catalogs = $this->schema->allowedCatalogs($actor);
        }

        $text = trim($sql);
        $text = rtrim($text, " \t\n\r;");

        foreach (array_reverse($references) as $reference) {
            $derived = $this->derived($reference['table'], $catalogs, $include_deleted);
            $replacement = $reference['alias'] ? $derived : $derived.' AS `'.$reference['table'].'`';
            $text = substr($text, 0, $reference['start']).$replacement.substr($text, $reference['end']);
        }

        $first = $tokens[0];
        $hint = ' /*+ MAX_EXECUTION_TIME('.(config('assistant.sql_timeout_seconds') * 1000).') */';
        $text = substr($text, 0, $first['e']).$hint.substr($text, $first['e']);

        return ['sql' => $text, 'tables' => $tables, 'catalogs' => $catalogs];
    }

    /**
     * @return array<int, array{t: string, v: string, s: int, e: int}>
     */
    private function tokenize(string $sql): array
    {
        $tokens = [];
        $length = strlen($sql);
        $i = 0;

        if ($length === 0) {
            throw McpFailure::invalidSql('La consulta está vacía.');
        }

        if ($length > 20000) {
            throw McpFailure::invalidSql('La consulta es demasiado larga (máximo 20.000 caracteres).');
        }

        while ($i < $length) {
            $char = $sql[$i];

            if (ctype_space($char)) {
                $i++;

                continue;
            }

            if ($char === '#' || ($char === '-' && ($sql[$i + 1] ?? '') === '-') || ($char === '/' && ($sql[$i + 1] ?? '') === '*')) {
                throw McpFailure::invalidSql('No se permiten comentarios en la consulta.');
            }

            if ($char === "'" || $char === '"') {
                $start = $i;
                $i++;

                while (true) {
                    if ($i >= $length) {
                        throw McpFailure::invalidSql('Hay un texto sin cerrar en la consulta.');
                    }

                    if ($sql[$i] === '\\') {
                        $i += 2;

                        continue;
                    }

                    if ($sql[$i] === $char) {
                        if (($sql[$i + 1] ?? '') === $char) {
                            $i += 2;

                            continue;
                        }

                        $i++;

                        break;
                    }

                    $i++;
                }

                $tokens[] = ['t' => 'string', 'v' => substr($sql, $start, $i - $start), 's' => $start, 'e' => $i];

                continue;
            }

            if ($char === '`') {
                $start = $i;
                $i++;

                while (true) {
                    if ($i >= $length) {
                        throw McpFailure::invalidSql('Hay un nombre entre comillas invertidas sin cerrar.');
                    }

                    if ($sql[$i] === '`') {
                        if (($sql[$i + 1] ?? '') === '`') {
                            $i += 2;

                            continue;
                        }

                        $i++;

                        break;
                    }

                    $i++;
                }

                $tokens[] = ['t' => 'ident', 'v' => substr($sql, $start + 1, $i - $start - 2), 's' => $start, 'e' => $i];

                continue;
            }

            if (ctype_digit($char)) {
                $start = $i;

                while ($i < $length && (ctype_alnum($sql[$i]) || $sql[$i] === '.')) {
                    $i++;
                }

                $tokens[] = ['t' => 'num', 'v' => substr($sql, $start, $i - $start), 's' => $start, 'e' => $i];

                continue;
            }

            if (ctype_alpha($char) || $char === '_' || $char === '$' || ord($char) > 127) {
                $start = $i;

                while ($i < $length && (ctype_alnum($sql[$i]) || $sql[$i] === '_' || $sql[$i] === '$' || ord($sql[$i]) > 127)) {
                    $i++;
                }

                $tokens[] = ['t' => 'word', 'v' => substr($sql, $start, $i - $start), 's' => $start, 'e' => $i];

                continue;
            }

            if ($char === '@') {
                throw McpFailure::invalidSql('No se permiten variables (@) en la consulta.');
            }

            $tokens[] = ['t' => 'sym', 'v' => $char, 's' => $i, 'e' => $i + 1];
            $i++;
        }

        return $tokens;
    }

    private function check(array $tokens): void
    {
        if (! $tokens || $tokens[0]['t'] !== 'word' || strtoupper($tokens[0]['v']) !== 'SELECT') {
            throw McpFailure::invalidSql('Solo se permiten consultas SELECT.');
        }

        foreach ($tokens as $token) {
            if ($token['t'] === 'sym' && $token['v'] === ';') {
                throw McpFailure::invalidSql('Solo se permite una sentencia por consulta.');
            }

            if ($token['t'] === 'word' && in_array(strtoupper($token['v']), self::FORBIDDEN_WORDS, true)) {
                throw McpFailure::invalidSql('La palabra «'.strtoupper($token['v']).'» no se permite en las consultas del asistente.');
            }
        }
    }

    /**
     * Las tablas que usa la consulta (las que siguen a FROM o JOIN), con su posición para reemplazarlas.
     *
     * @return array<int, array{table: string, start: int, end: int, alias: bool}>
     */
    private function references(array $tokens): array
    {
        $references = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token['t'] !== 'word' || ! in_array(strtoupper($token['v']), ['FROM', 'JOIN'], true)) {
                continue;
            }

            $next = $tokens[$i + 1] ?? null;

            if (! $next) {
                continue;
            }

            if ($next['t'] === 'sym' && $next['v'] === '(') {
                $inner = $tokens[$i + 2] ?? null;

                if (! $inner || $inner['t'] !== 'word' || strtoupper($inner['v']) !== 'SELECT') {
                    throw McpFailure::invalidSql('Después de FROM o JOIN solo se permite el nombre de una tabla o una subconsulta (SELECT ...), no tablas entre paréntesis.');
                }

                $this->assertNoImplicitJoin($tokens, $i);

                continue;
            }

            if (! in_array($next['t'], ['word', 'ident'], true)) {
                throw McpFailure::invalidSql('Después de FROM o JOIN debe ir el nombre de una tabla.');
            }

            $after = $tokens[$i + 2] ?? null;

            if ($after && $after['t'] === 'sym' && $after['v'] === '.') {
                throw McpFailure::invalidSql('No se permiten nombres de tabla con esquema (base.tabla). Usa solo el nombre de la tabla.');
            }

            $alias = false;
            $end_index = $i + 1;

            if ($after && $after['t'] === 'word' && strtoupper($after['v']) === 'AS') {
                $alias = true;
                $end_index = $i + 3;
            } elseif ($after && in_array($after['t'], ['word', 'ident'], true) && ! in_array(strtoupper($after['v']), self::NOT_ALIAS, true)) {
                $alias = true;
                $end_index = $i + 2;
            }

            $this->assertNoImplicitJoin($tokens, $i);

            $references[] = ['table' => strtolower($next['v']), 'start' => $next['s'], 'end' => $next['e'], 'alias' => $alias];
        }

        return $references;
    }

    /**
     * Dentro de una cláusula FROM, una coma fuera de paréntesis separa tablas (join implícito) y esa segunda tabla no se
     * revisaría. Las comas de funciones, IN (...) y USING (...) van entre paréntesis y no cuentan.
     */
    private function assertNoImplicitJoin(array $tokens, int $from): void
    {
        $depth = 0;
        $count = count($tokens);

        for ($j = $from + 1; $j < $count; $j++) {
            $token = $tokens[$j];

            if ($token['t'] === 'sym' && $token['v'] === '(') {
                $depth++;
            } elseif ($token['t'] === 'sym' && $token['v'] === ')') {
                if ($depth === 0) {
                    return;
                }

                $depth--;
            } elseif ($depth === 0 && $token['t'] === 'word' && in_array(strtoupper($token['v']), self::CLAUSE_ENDS, true)) {
                return;
            } elseif ($depth === 0 && $token['t'] === 'sym' && $token['v'] === ',') {
                throw McpFailure::invalidSql('No se permite listar tablas separadas por coma en FROM. Usa JOIN ... ON.');
            }
        }
    }

    private function authorize(User $actor, string $table): void
    {
        if ($this->schema->isForbidden($table)) {
            throw McpFailure::tableNotAllowed($table, "La tabla {$table} no se puede consultar desde el asistente.");
        }

        if (! $this->schema->isKnown($table)) {
            throw McpFailure::tableNotAllowed($table, "La tabla «{$table}» no existe o no está habilitada para el asistente. Usa describe_tables para ver las tablas disponibles. (Si usas EXTRACT, TRIM o SUBSTRING con FROM, cámbialo por YEAR(), MONTH(), TRIM(col) o SUBSTR(col, n).)");
        }

        if ($this->schema->canRead($actor, $table)) {
            return;
        }

        $catalog_table = config('assistant_tables.catalog_table');
        $items_table = config('assistant_tables.catalog_items_table');

        if ($table === $catalog_table || $table === $items_table) {
            throw McpFailure::forbidden([], 'No tienes permiso para ver esta información en Kanri. Pídeselo a un administrador.');
        }

        $permissions = $this->schema->permissions($table);
        $titles = Permission::whereIn('name', $permissions)->pluck('title')->unique()->join(', ');

        throw McpFailure::forbidden($permissions, 'No tienes permiso para ver esta información en Kanri.'.($titles !== '' ? ' Te falta uno de estos permisos: '.$titles.'.' : '').' Pídeselo a un administrador.');
    }

    /**
     * La consulta que reemplaza a la tabla: solo sus columnas permitidas y solo las filas que la persona puede ver.
     */
    private function derived(string $table, ?array $catalogs, bool $include_deleted): string
    {
        $columns = implode(', ', array_map(fn (array $column) => '`'.$column['name'].'`', $this->schema->columns($table)));
        $filters = [];

        if ($table === config('assistant_tables.catalog_table')) {
            $filters[] = '`item_id` IN ('.implode(', ', array_map('intval', array_keys($catalogs ?? []))).')';
        }

        if (! $include_deleted && $this->schema->hasColumn($table, 'deleted_at')) {
            $filters[] = '`deleted_at` IS NULL';
        }

        return '(SELECT '.$columns.' FROM `'.$table.'`'.($filters ? ' WHERE '.implode(' AND ', $filters) : '').')';
    }
}
