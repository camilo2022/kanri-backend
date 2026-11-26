<?php

namespace App\Mcp\Prompts\Color;

use App\Mcp\Prompts\RoutePrompt;

class ColorAuditPrompt extends RoutePrompt
{
    protected string $name = 'color_audit';

    protected string $title = 'Historial de cambios de un color';

    protected string $description = 'Historial de cambios de un color';

    protected string $method = 'GET';

    protected string $path = '/colors/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de color: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
