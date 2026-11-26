<?php

namespace App\Mcp\Prompts\Subgroup;

use App\Mcp\Prompts\RoutePrompt;

class SubgroupFindPrompt extends RoutePrompt
{
    protected string $name = 'subgroup_find';

    protected string $title = 'Obtener un subgrupo';

    protected string $description = 'Obtener un subgrupo';

    protected string $method = 'GET';

    protected string $path = '/classification/subgroups/find/{id}';

    protected string $business = 'Trae un solo registro de subgrupo por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
