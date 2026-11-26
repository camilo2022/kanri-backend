<?php

namespace App\Mcp\Prompts\Process;

use App\Mcp\Prompts\RoutePrompt;

class ProcessAuditPrompt extends RoutePrompt
{
    protected string $name = 'process_audit';

    protected string $title = 'Historial de cambios de un proceso';

    protected string $description = 'Historial de cambios de un proceso';

    protected string $method = 'GET';

    protected string $path = '/workflow/processes/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de proceso: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
