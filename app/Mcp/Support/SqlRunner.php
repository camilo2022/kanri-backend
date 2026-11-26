<?php

namespace App\Mcp\Support;

use App\Mcp\McpFailure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel;
use Throwable;

/**
 * Ejecuta las consultas SQL del asistente, ya revisadas por SqlGate, con el usuario de base de datos de solo lectura.
 * Lee fila por fila y se detiene al llegar al tope de filas o de tamaño, así una consulta enorme no llena la memoria
 * ni el chat. Si se pide, deja el resultado completo en un Excel con enlace de descarga.
 */
class SqlRunner
{
    public function __construct(private SqlGate $gate, private FileLink $files) {}

    /**
     * @return array<string, mixed>
     */
    public function run(User $actor, string $sql, ?int $max_rows = null, bool $include_deleted = false): array
    {
        $prepared = $this->gate->prepare($actor, $sql, $include_deleted);

        $max_rows = min(max($max_rows ?? config('assistant.sql_max_rows'), 1), config('assistant.sql_max_rows_limit'));
        $max_chars = config('assistant.sql_max_chars');

        $rows = [];
        $chars = 0;
        $truncated = null;

        try {
            foreach ($this->connection()->cursor($prepared['sql']) as $row) {
                $row = (array) $row;
                $size = strlen(json_encode($row, JSON_UNESCAPED_UNICODE) ?: '');

                if (count($rows) >= $max_rows) {
                    $truncated = 'rows';

                    break;
                }

                if ($chars + $size > $max_chars && $rows) {
                    $truncated = 'chars';

                    break;
                }

                $rows[] = $row;
                $chars += $size;
            }
        } catch (Throwable $e) {
            throw $this->failure($e);
        }

        return $this->result($prepared, $rows, $truncated, $max_rows);
    }

    /**
     * Ejecuta la consulta y guarda todas sus filas (hasta el tope) en un Excel; devuelve el enlace de descarga.
     *
     * @return array<string, mixed>
     */
    public function export(User $actor, string $sql, bool $include_deleted = false, string $name = 'consulta'): array
    {
        $prepared = $this->gate->prepare($actor, $sql, $include_deleted);
        $max = config('assistant.sql_export_max_rows');

        $rows = [];
        $headings = [];
        $truncated = false;

        try {
            foreach ($this->connection()->cursor($prepared['sql']) as $row) {
                if (count($rows) >= $max) {
                    $truncated = true;

                    break;
                }

                $row = (array) $row;
                $headings = $headings ?: array_keys($row);
                $rows[] = array_map(fn ($value) => is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE), $row);
            }
        } catch (Throwable $e) {
            throw $this->failure($e);
        }

        $export = new class($rows, $headings) implements FromArray, ShouldAutoSize, WithHeadings
        {
            public function __construct(private array $rows, private array $headings) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return $this->headings;
            }
        };

        $content = app(Excel::class)->raw($export, Excel::XLSX);
        $file = $this->files->store($content, preg_replace('/[^\w\-]/', '_', $name).'.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return [
            'ok' => true,
            'row_count' => count($rows),
            'truncated' => $truncated,
            'tables' => $prepared['tables'],
            'file' => $file,
            'message' => 'El Excel está listo. Entrégale a la persona el enlace file.url: lo descarga con su navegador y vence en '.config('assistant.file_minutes').' minutos.'.($truncated ? ' Se cortó en '.$max.' filas: pide un filtro más estrecho si necesita el resto.' : ''),
        ];
    }

    private function connection()
    {
        $name = config('assistant.sql_connection');

        if (! config("database.connections.{$name}.username")) {
            throw McpFailure::server('Las consultas SQL del asistente no están configuradas en este servidor (falta el usuario de base de datos de solo lectura).');
        }

        return DB::connection($name);
    }

    private function result(array $prepared, array $rows, ?string $truncated, int $max_rows): array
    {
        $result = [
            'ok' => true,
            'row_count' => count($rows),
            'truncated' => $truncated !== null,
            'tables' => $prepared['tables'],
            'rows' => $rows,
        ];

        if ($truncated === 'rows') {
            $result['message'] = "Se devolvieron las primeras {$max_rows} filas y hay más. Pide un filtro más estrecho, usa LIMIT y OFFSET para paginar, o pide el resultado completo en un Excel (export=true).";
        }

        if ($truncated === 'chars') {
            $result['message'] = 'El resultado se cortó por tamaño. Pide menos columnas, un filtro más estrecho, o el resultado completo en un Excel (export=true).';
        }

        if ($prepared['catalogs'] !== null) {
            $result['catalogs_visible'] = $prepared['catalogs'];
        }

        return $result;
    }

    private function failure(Throwable $e): McpFailure
    {
        $message = $e->getMessage();

        if (str_contains($message, 'maximum statement execution time')) {
            return McpFailure::server('La consulta tardó más de '.config('assistant.sql_timeout_seconds').' segundos y se canceló. Pide menos columnas, un filtro más estrecho o usa agregaciones (COUNT, SUM, GROUP BY).');
        }

        if (preg_match('/Unknown column|Unknown table|doesn\'t exist|syntax|Unknown database|Column .* in field list is ambiguous|ambiguous/i', $message)) {
            return McpFailure::invalidSql('La base de datos rechazó la consulta: '.preg_replace('/\s*\(Connection:.*$/s', '', $message));
        }

        if (str_contains($message, 'Access denied') || str_contains($message, 'command denied')) {
            return McpFailure::server('El usuario de solo lectura no tiene acceso a lo que pide la consulta.');
        }

        return McpFailure::server('Kanri no pudo ejecutar la consulta.');
    }
}
