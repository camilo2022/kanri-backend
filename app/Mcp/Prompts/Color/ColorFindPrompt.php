<?php

namespace App\Mcp\Prompts\Color;

use App\Mcp\Prompts\RoutePrompt;

class ColorFindPrompt extends RoutePrompt
{
    protected string $name = 'color_find';

    protected string $title = 'Obtener un color';

    protected string $description = 'Obtener un color';

    protected string $method = 'GET';

    protected string $path = '/colors/find/{id}';

    protected string $business = 'Muestra un color concreto con su detalle, incluida su configuración (por ejemplo si aplica a producto terminado). Úsala cuando la persona quiera ver un color puntual y ya se conoce su id. Es solo consulta; si no existe responde que no se encontró.';
}
