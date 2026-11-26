<?php

namespace App\Mcp\Prompts\Piece;

use App\Mcp\Prompts\RoutePrompt;

class PieceFindPrompt extends RoutePrompt
{
    protected string $name = 'piece_find';

    protected string $title = 'Obtener una pieza';

    protected string $description = 'Obtener una pieza';

    protected string $method = 'GET';

    protected string $path = '/pieces/find/{id}';

    protected string $business = 'Trae un solo registro de pieza por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
