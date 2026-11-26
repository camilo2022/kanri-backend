<?php

namespace App\Mcp\Prompts\CompensationFund;

use App\Mcp\Prompts\RoutePrompt;

class CompensationFundAuditPrompt extends RoutePrompt
{
    protected string $name = 'compensation_fund_audit';

    protected string $title = 'Historial de cambios de una caja de compensación';

    protected string $description = 'Historial de cambios de una caja de compensación';

    protected string $method = 'GET';

    protected string $path = '/compensation_funds/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de caja de compensación: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
