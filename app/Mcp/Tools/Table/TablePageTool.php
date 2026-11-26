<?php

namespace App\Mcp\Tools\Table;

use App\Mcp\McpFailure;
use App\Mcp\Resources\TableViewerApp;
use App\Mcp\Support\Dataset;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\RendersApp;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Ui\Enums\Visibility;

#[Name('table_page')]
#[Title('Pedir una página de la tabla')]
#[Description('USO INTERNO DE LA TABLA EN PANTALLA: devuelve una página del resultado de show_table con su código. No la llames tú: la pantalla la usa para paginar, buscar y ordenar sin pasar los datos por el chat.')]
#[RendersApp(TableViewerApp::class, visibility: [Visibility::App])]
#[IsReadOnly]
#[IsIdempotent]
class TablePageTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            $page = app(Dataset::class)->page(
                (string) $request->get('token'),
                (int) $request->get('page', 1),
                (int) $request->get('per_page', 50),
                (string) $request->get('search', ''),
                $request->get('sort') !== null && $request->get('sort') !== '' ? (int) $request->get('sort') : null,
                $request->get('dir') === 'desc' ? 'desc' : 'asc'
            );
        } catch (McpFailure $failure) {
            return $failure->response();
        }

        return Response::json($page);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'token' => $schema->string()->description('Código del resultado que entregó show_table.')->required(),
            'page' => $schema->integer()->description('Número de página, desde 1.'),
            'per_page' => $schema->integer()->description('Filas por página (máximo 200).'),
            'search' => $schema->string()->description('Texto a buscar en cualquier columna.'),
            'sort' => $schema->integer()->description('Posición de la columna por la que se ordena, desde 0.'),
            'dir' => $schema->string()->description('asc o desc.'),
        ];
    }
}
