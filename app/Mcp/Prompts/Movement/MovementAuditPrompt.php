<?php

namespace App\Mcp\Prompts\Movement;

use App\Mcp\Prompts\RoutePrompt;

class MovementAuditPrompt extends RoutePrompt
{
    protected string $name = 'movement_audit';

    protected string $title = 'Historial de cambios de un movimiento';

    protected string $description = 'Historial de cambios de un movimiento';

    protected string $method = 'GET';

    protected string $path = '/movements/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de movimiento con sus traslados: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
