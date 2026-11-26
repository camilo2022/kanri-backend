<?php

namespace App\Mcp\Support;

use App\Mcp\McpFailure;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Archivos que devuelven las rutas (Excel, etc.) cuando las pide el asistente. El chat no puede recibir un archivo, así
 * que se guarda unos minutos y se entrega una dirección firmada y temporal para que la persona lo descargue con su
 * navegador. Mismo mecanismo que los PDF (PdfLinkService).
 */
class FileLink
{
    private function dir(): string
    {
        return storage_path('app/temp/assistant');
    }

    public function path(string $token): string
    {
        return $this->dir().'/'.preg_replace('/[^a-f0-9]/', '', $token);
    }

    /**
     * Guarda el archivo y devuelve sus datos con la dirección firmada de descarga.
     *
     * @return array{name: string, mime: string, size: int, url: string, expires_in_minutes: int}
     */
    public function store(string $content, string $name, string $mime): array
    {
        if (strlen($content) > config('assistant.max_file_mb') * 1024 * 1024) {
            throw McpFailure::tooLarge(['size' => strlen($content), 'limit' => config('assistant.max_file_mb').' MB']);
        }

        if (! is_dir($this->dir())) {
            mkdir($this->dir(), 0777, true);
        }

        foreach (glob($this->dir().'/*') ?: [] as $old) {
            if (filemtime($old) < now()->subMinutes(config('assistant.file_minutes') * 2)->getTimestamp()) {
                @unlink($old);
            }
        }

        $token = bin2hex(random_bytes(20));

        file_put_contents($this->path($token), $content);
        file_put_contents($this->path($token).'.json', json_encode(['name' => $name, 'mime' => $mime]));

        return [
            'name' => $name,
            'mime' => $mime,
            'size' => strlen($content),
            'url' => URL::temporarySignedRoute('assistant.file', now()->addMinutes(config('assistant.file_minutes')), ['token' => $token]),
            'expires_in_minutes' => config('assistant.file_minutes'),
        ];
    }

    /**
     * @return array{path: string, name: string, mime: string}|null
     */
    public function find(string $token): ?array
    {
        $path = $this->path($token);
        $meta = json_decode((string) @file_get_contents($path.'.json'), true);

        if (! is_file($path) || ! is_array($meta) || filemtime($path) < now()->subMinutes(config('assistant.file_minutes') * 2)->getTimestamp()) {
            return null;
        }

        return ['path' => $path, 'name' => Str::of($meta['name'])->replaceMatches('/[^\w.\- ]/u', '_')->toString(), 'mime' => $meta['mime']];
    }
}
