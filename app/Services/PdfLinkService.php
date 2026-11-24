<?php

namespace App\Services;

use Illuminate\Support\Facades\URL;

/**
 * PDF que se abren desde el sistema: el backend los genera, los guarda unos minutos y devuelve una dirección firmada que
 * termina en el nombre del PDF. El navegador abre esa dirección cuando el PDF ya está listo (no como blob, que salía con
 * un id) y lo muestra y guarda con ese nombre. La misma dirección sin archivo guardado (QR) genera el PDF de nuevo.
 */
class PdfLinkService
{
    private const MINUTES = 5;

    private function path(string $route, string $uuid, string $file_name): string
    {
        return storage_path('app/temp/pdf/'.sha1($route.'|'.$uuid.'|'.$file_name).'.pdf');
    }

    /**
     * Guarda el PDF y devuelve la dirección firmada (vence en 5 minutos) que lo entrega.
     */
    public function store(string $route, string $uuid, string $file_name, string $content): string
    {
        $dir = storage_path('app/temp/pdf');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Los guardados que ya vencieron se borran
        foreach (glob($dir.'/*.pdf') ?: [] as $old) {
            if (filemtime($old) < now()->subMinutes(self::MINUTES * 2)->getTimestamp()) {
                @unlink($old);
            }
        }

        file_put_contents($this->path($route, $uuid, $file_name), $content);

        return URL::temporarySignedRoute($route, now()->addMinutes(self::MINUTES), ['uuid' => $uuid, 'name' => $file_name]);
    }

    /**
     * El PDF guardado para esa dirección, si todavía está; si no, null y el controlador lo genera.
     */
    public function cached(string $route, string $uuid, ?string $file_name)
    {
        $path = $file_name ? $this->path($route, $uuid, $file_name) : null;
        if (! $path || ! is_file($path) || filemtime($path) < now()->subMinutes(self::MINUTES * 2)->getTimestamp()) {
            return null;
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$file_name.'"',
        ]);
    }
}
