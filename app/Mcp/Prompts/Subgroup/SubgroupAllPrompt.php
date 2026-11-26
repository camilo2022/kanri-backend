<?php

namespace App\Mcp\Prompts\Subgroup;

use App\Mcp\Prompts\RoutePrompt;

class SubgroupAllPrompt extends RoutePrompt
{
    protected string $name = 'subgroup_all';

    protected string $title = 'Listar los subgrupos';

    protected string $description = 'Listar los subgrupos';

    protected string $method = 'GET';

    protected string $path = '/classification/subgroups/all';

    protected string $business = 'Lista los subgrupos, un nivel de clasificación del producto más fino que el grupo. Úsala cuando pregunten qué subgrupos existen. Trae nombre y descripción, sin la configuración (settings). Admite búsqueda por texto y with_trashed para incluir los inactivos (eliminados lógicamente), que por defecto no aparecen.';
}
