<?php

namespace App\Mcp\Prompts\Size;

use App\Mcp\Prompts\RoutePrompt;

class SizeFindPrompt extends RoutePrompt
{
    protected string $name = 'size_find';

    protected string $title = 'Obtener una talla';

    protected string $description = 'Obtener una talla';

    protected string $method = 'GET';

    protected string $path = '/sizes/find/{id}';

    protected string $business = 'Trae un solo registro de talla por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
