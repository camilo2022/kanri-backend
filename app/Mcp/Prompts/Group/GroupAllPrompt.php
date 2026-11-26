<?php

namespace App\Mcp\Prompts\Group;

use App\Mcp\Prompts\RoutePrompt;

class GroupAllPrompt extends RoutePrompt
{
    protected string $name = 'group_all';

    protected string $title = 'Listar los grupos';

    protected string $description = 'Listar los grupos';

    protected string $method = 'GET';

    protected string $path = '/classification/groups/all';

    protected string $business = 'Lista los grupos, una clasificación del producto que cuelga de la marca: cada marca pertenece a un grupo. Úsala cuando pregunten qué grupos hay para clasificar marcas. Trae nombre y descripción, sin la configuración (settings). Admite búsqueda por texto y with_trashed para incluir los grupos inactivos (eliminados lógicamente), que por defecto no aparecen. Para ver qué marcas tiene un grupo hay que consultar las marcas, que sí traen su grupo.';
}
