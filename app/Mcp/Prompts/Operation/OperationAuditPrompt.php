<?php

namespace App\Mcp\Prompts\Operation;

use App\Mcp\Prompts\RoutePrompt;

class OperationAuditPrompt extends RoutePrompt
{
    protected string $name = 'operation_audit';

    protected string $title = 'Historial de cambios de una operación';

    protected string $description = 'Historial de cambios de una operación';

    protected string $method = 'GET';

    protected string $path = '/workflow/processes/subprocesses/operations/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de operación: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
