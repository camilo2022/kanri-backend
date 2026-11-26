<?php

namespace App\Mcp\Support;

use App\Mcp\McpFailure;
use Generator;
use Illuminate\Support\Str;

/**
 * Resultado de una consulta SQL guardado unos minutos para mostrarlo en la tabla de la pantalla (show_table). Se guarda en
 * un archivo con una fila por línea y un archivo de datos aparte; la pantalla pide página por página, así el resultado
 * completo nunca pasa por el chat. El código del resultado es secreto y de vida corta, como el del enlace del Excel.
 */
class Dataset
{
    private function dir(): string
    {
        return storage_path('app/temp/assistant');
    }

    private function path(string $token): string
    {
        return $this->dir().'/ds_'.preg_replace('/[^a-f0-9]/', '', $token);
    }

    /**
     * @param  iterable<int, array<int, mixed>>  $rows  cada fila es una lista de valores en el orden de las columnas
     * @param  callable(): array<int, string>  $columns  las columnas; se pide al terminar de leer las filas
     * @param  array<string, mixed>  $meta
     * @return array{token: string, row_count: int}
     */
    public function create(iterable $rows, callable $columns, array $meta = []): array
    {
        if (! is_dir($this->dir())) {
            mkdir($this->dir(), 0777, true);
        }

        $this->clean();

        $token = bin2hex(random_bytes(20));
        $handle = fopen($this->path($token).'.jsonl', 'w');
        $count = 0;

        foreach ($rows as $row) {
            fwrite($handle, json_encode(array_map(fn ($value) => $this->cell($value), array_values($row)), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n");
            $count++;
        }

        fclose($handle);

        file_put_contents($this->path($token).'.json', json_encode($meta + ['columns' => $columns(), 'row_count' => $count, 'created_at' => now()->timestamp], JSON_UNESCAPED_UNICODE));

        return ['token' => $token, 'row_count' => $count];
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(string $token): array
    {
        $meta = json_decode((string) @file_get_contents($this->path($token).'.json'), true);

        if (! is_array($meta) || ($meta['created_at'] ?? 0) < now()->subMinutes($this->minutes())->timestamp || ! is_file($this->path($token).'.jsonl')) {
            throw McpFailure::notFound('El resultado ya no está disponible (vence a los '.$this->minutes().' minutos). Vuelve a ejecutar la consulta.');
        }

        return $meta;
    }

    /**
     * Una página del resultado, con búsqueda de texto y orden por columna.
     *
     * @return array<string, mixed>
     */
    public function page(string $token, int $page = 1, int $per_page = 50, string $search = '', ?int $sort = null, string $direction = 'asc'): array
    {
        $meta = $this->meta($token);
        $per_page = min(max($per_page, 1), 200);
        $page = max($page, 1);
        $search = trim($search);
        $sort = $sort !== null && isset($meta['columns'][$sort]) ? $sort : null;

        $matches = [];
        $matched = 0;
        $window = [];
        $offset = ($page - 1) * $per_page;

        foreach ($this->lines($token) as $line) {
            if ($search !== '' && ! $this->matches($line, $search)) {
                continue;
            }

            if ($sort === null) {
                if ($matched >= $offset && count($window) < $per_page) {
                    $window[] = json_decode($line, true);
                }
            } else {
                $row = json_decode($line, true);
                $matches[] = $row;
            }

            $matched++;
        }

        if ($sort !== null) {
            usort($matches, function (array $a, array $b) use ($sort, $direction) {
                $x = $a[$sort] ?? null;
                $y = $b[$sort] ?? null;
                $result = is_numeric($x) && is_numeric($y) ? $x <=> $y : strnatcasecmp((string) $x, (string) $y);

                return $direction === 'desc' ? -$result : $result;
            });

            $window = array_slice($matches, $offset, $per_page);
        }

        return [
            'ok' => true,
            'columns' => $meta['columns'],
            'total' => $meta['row_count'],
            'filtered' => $matched,
            'page' => $page,
            'pages' => max((int) ceil($matched / $per_page), 1),
            'per_page' => $per_page,
            'rows' => $window,
        ];
    }

    /**
     * Todas las filas, una por una (para el Excel).
     *
     * @return Generator<int, array<int, mixed>>
     */
    public function rows(string $token): Generator
    {
        $this->meta($token);

        foreach ($this->lines($token) as $line) {
            yield json_decode($line, true);
        }
    }

    private function matches(string $line, string $search): bool
    {
        foreach (json_decode($line, true) ?: [] as $value) {
            if ($value !== null && mb_stripos((string) $value, $search) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Generator<int, string>
     */
    private function lines(string $token): Generator
    {
        $handle = fopen($this->path($token).'.jsonl', 'r');

        try {
            while (($line = fgets($handle)) !== false) {
                yield rtrim($line, "\r\n");
            }
        } finally {
            fclose($handle);
        }
    }

    private function cell(mixed $value): mixed
    {
        if ($value === null || is_int($value) || is_float($value) || is_bool($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return Str::limit((string) $value, 2000, '…');
        }

        return Str::limit((string) json_encode($value, JSON_UNESCAPED_UNICODE), 2000, '…');
    }

    private function minutes(): int
    {
        return (int) config('assistant.dataset_minutes', 20);
    }

    private function clean(): void
    {
        foreach (glob($this->dir().'/ds_*') ?: [] as $old) {
            if (filemtime($old) < now()->subMinutes($this->minutes() * 2)->getTimestamp()) {
                @unlink($old);
            }
        }
    }
}
