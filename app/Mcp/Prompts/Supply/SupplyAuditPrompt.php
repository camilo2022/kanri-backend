<?php

namespace App\Mcp\Prompts\Supply;

use App\Mcp\Prompts\RoutePrompt;

class SupplyAuditPrompt extends RoutePrompt
{
    protected string $name = 'supply_audit';

    protected string $title = 'Historial de cambios de un insumo';

    protected string $description = 'Historial de cambios de un insumo';

    protected string $method = 'GET';

    protected string $path = '/typification/supply_types/supplies/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de insumo: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
