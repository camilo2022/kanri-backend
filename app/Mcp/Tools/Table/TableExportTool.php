<?php

namespace App\Mcp\Tools\Table;

use App\Mcp\McpFailure;
use App\Mcp\Resources\TableViewerApp;
use App\Mcp\Support\SqlRunner;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\RendersApp;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Ui\Enums\Visibility;

#[Name('table_export')]
#[Title('Descargar la tabla en Excel')]
#[Description('USO INTERNO DE LA TABLA EN PANTALLA: genera el Excel del resultado de show_table y devuelve el enlace de descarga. No la llames tú: la pantalla la usa en su botón de Excel.')]
#[RendersApp(TableViewerApp::class, visibility: [Visibility::App])]
#[IsReadOnly]
class TableExportTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            $file = app(SqlRunner::class)->excelFromDataset((string) $request->get('token'), (string) ($request->get('name') ?: 'consulta'));
        } catch (McpFailure $failure) {
            return $failure->response();
        }

        return Response::json(['ok' => true, 'file' => $file]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'token' => $schema->string()->description('Código del resultado que entregó show_table.')->required(),
            'name' => $schema->string()->description('Nombre del archivo, sin extensión.'),
        ];
    }
}
