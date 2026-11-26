<?php

namespace App\Mcp\Prompts\Area;

use App\Mcp\Prompts\RoutePrompt;

class AreaFindPrompt extends RoutePrompt
{
    protected string $name = 'area_find';

    protected string $title = 'Obtener un área';

    protected string $description = 'Obtener un área';

    protected string $method = 'GET';

    protected string $path = '/organizational_structure/areas/find/{id}';

    protected string $business = 'Trae un solo registro de área por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
