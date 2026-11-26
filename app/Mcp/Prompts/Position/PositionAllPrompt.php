<?php

namespace App\Mcp\Prompts\Position;

use App\Mcp\Prompts\RoutePrompt;

class PositionAllPrompt extends RoutePrompt
{
    protected string $name = 'position_all';

    protected string $title = 'Listar los cargos de un área';

    protected string $description = 'Listar los cargos de un área';

    protected string $method = 'GET';

    protected string $path = '/organizational_structure/areas/positions/all/{area_id}';

    protected string $business = 'Lista los cargos de un área; el id del área va en la ruta. Cada cargo trae sus roles y permisos, que son los que reciben automáticamente los empleados que lo ocupan. Úsala cuando pregunten qué cargos tiene un área o qué puede hacer quien ocupa un cargo. Cada cargo llega con su área, sus roles y sus permisos. Admite búsqueda por texto y with_trashed para incluir los inactivos. Un área inexistente simplemente devuelve una lista vacía.';
}
