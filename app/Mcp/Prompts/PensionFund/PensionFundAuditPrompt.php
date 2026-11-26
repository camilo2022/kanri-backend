<?php

namespace App\Mcp\Prompts\PensionFund;

use App\Mcp\Prompts\RoutePrompt;

class PensionFundAuditPrompt extends RoutePrompt
{
    protected string $name = 'pension_fund_audit';

    protected string $title = 'Historial de cambios de un fondo de pensión';

    protected string $description = 'Historial de cambios de un fondo de pensión';

    protected string $method = 'GET';

    protected string $path = '/pension_funds/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de fondo de pensión: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
