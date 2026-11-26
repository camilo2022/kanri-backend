<?php

namespace App\Mcp\Prompts\Stop;

use App\Mcp\Prompts\RoutePrompt;

class StopAuditPrompt extends RoutePrompt
{
    protected string $name = 'stop_audit';

    protected string $title = 'Historial de cambios de una parada';

    protected string $description = 'Historial de cambios de una parada';

    protected string $method = 'GET';

    protected string $path = '/typification/stop_types/stops/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de parada: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
