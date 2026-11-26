<?php

namespace App\Http\Controllers;

use App\Mcp\Support\FileLink;

/**
 * Entrega los archivos que pidió el asistente (Excel, etc.). Solo se llega con la dirección firmada y temporal que
 * devuelve la herramienta; sin firma o vencida, no hay descarga.
 */
class AssistantFileController extends Controller
{
    public function show(string $token)
    {
        $file = app(FileLink::class)->find($token);

        abort_if(! $file, 404, 'El archivo ya no está disponible. Pídelo de nuevo en el chat.');

        return response()->download($file['path'], $file['name'], ['Content-Type' => $file['mime']]);
    }
}
