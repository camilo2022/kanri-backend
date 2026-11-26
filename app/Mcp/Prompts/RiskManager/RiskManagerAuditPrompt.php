<?php

namespace App\Mcp\Prompts\RiskManager;

use App\Mcp\Prompts\RoutePrompt;

class RiskManagerAuditPrompt extends RoutePrompt
{
    protected string $name = 'risk_manager_audit';

    protected string $title = 'Historial de cambios de una administradora de riesgos laborales (ARL)';

    protected string $description = 'Historial de cambios de una administradora de riesgos laborales (ARL)';

    protected string $method = 'GET';

    protected string $path = '/risk_managers/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de administradora de riesgos: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
