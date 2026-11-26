<?php

namespace App\Mcp\Prompts\Position;

use App\Mcp\Prompts\RoutePrompt;

class PositionFindPrompt extends RoutePrompt
{
    protected string $name = 'position_find';

    protected string $title = 'Obtener un cargo';

    protected string $description = 'Obtener un cargo';

    protected string $method = 'GET';

    protected string $path = '/organizational_structure/areas/positions/find/{id}';

    protected string $business = 'Muestra un cargo concreto con su área, sus roles y sus permisos. Úsala cuando pregunten qué tiene asignado un cargo puntual y ya se conoce su id. Incluye también la configuración (settings) del cargo. Es solo consulta; si no existe responde que no se encontró.';
}
