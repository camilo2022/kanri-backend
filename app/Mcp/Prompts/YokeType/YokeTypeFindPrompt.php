<?php

namespace App\Mcp\Prompts\YokeType;

use App\Mcp\Prompts\RoutePrompt;

class YokeTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'yoke_type_find';

    protected string $title = 'Obtener un tipo de cotilla';

    protected string $description = 'Obtener un tipo de cotilla';

    protected string $method = 'GET';

    protected string $path = '/typification/yoke_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de cotilla por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
