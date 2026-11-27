<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\AppResource;
use Laravel\Mcp\Server\Attributes\AppMeta;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Tabla interactiva de Kanri: muestra todas las filas del resultado de show_table en una lista con desplazamiento, con buscador, orden por columna, botón de Excel y botón para ampliarla. Carga los datos por bloques desde el servidor, así que no depende del tamaño del chat.')]
#[AppMeta]
class TableViewerApp extends AppResource
{
    public function handle(Request $request): Response
    {
        return Response::view('mcp.table-viewer', ['title' => 'Tabla de Kanri']);
    }
}
