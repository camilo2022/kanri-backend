<?php

namespace App\Mcp\Prompts\Position;

use App\Mcp\Prompts\RoutePrompt;

class PositionAuditPrompt extends RoutePrompt
{
    protected string $name = 'position_audit';

    protected string $title = 'Historial de cambios de un cargo';

    protected string $description = 'Historial de cambios de un cargo';

    protected string $method = 'GET';

    protected string $path = '/organizational_structure/areas/positions/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de cargo: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
