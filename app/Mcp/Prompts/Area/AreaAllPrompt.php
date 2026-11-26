<?php

namespace App\Mcp\Prompts\Area;

use App\Mcp\Prompts\RoutePrompt;

class AreaAllPrompt extends RoutePrompt
{
    protected string $name = 'area_all';

    protected string $title = 'Listar áreas';

    protected string $description = 'Listar áreas';

    protected string $method = 'GET';

    protected string $path = '/organizational_structure/areas/all';

    protected string $business = 'Lista las áreas de la empresa; cada área agrupa los cargos que le pertenecen y cada empleado ocupa un cargo. Úsala cuando pregunten qué áreas hay o qué cargos tiene cada una. Cada área llega con sus cargos (positions), sin su configuración (settings). Admite búsqueda por texto y with_trashed para incluir las áreas inactivas (eliminadas lógicamente), que por defecto no aparecen.';
}
