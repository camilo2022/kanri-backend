<?php

namespace App\Mcp\Prompts\BackType;

use App\Mcp\Prompts\RoutePrompt;

class BackTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'back_type_find';

    protected string $title = 'Obtener un tipo de trasero';

    protected string $description = 'Obtener un tipo de trasero';

    protected string $method = 'GET';

    protected string $path = '/typification/back_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de trasero por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
