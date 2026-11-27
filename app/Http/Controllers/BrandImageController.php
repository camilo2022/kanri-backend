<?php

namespace App\Http\Controllers;

/**
 * Entrega los logos de las páginas de Kanri (inicio de sesión y autorización del asistente) directamente desde el
 * repositorio, así se ven aunque la carpeta pública del servidor no tenga una copia de public/images/brand.
 */
class BrandImageController extends Controller
{
    public function __invoke(string $file)
    {
        $path = base_path("public/images/brand/{$file}.png");

        abort_unless(is_file($path), 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }
}
