<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkAuditPrompt extends RoutePrompt
{
    protected string $name = 'trademark_audit';

    protected string $title = 'Historial de cambios de una marca';

    protected string $description = 'Historial de cambios de una marca';

    protected string $method = 'GET';

    protected string $path = '/trademarks/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de marca: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
