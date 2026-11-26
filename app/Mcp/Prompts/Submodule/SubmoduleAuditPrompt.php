<?php

namespace App\Mcp\Prompts\Submodule;

use App\Mcp\Prompts\RoutePrompt;

class SubmoduleAuditPrompt extends RoutePrompt
{
    protected string $name = 'submodule_audit';

    protected string $title = 'Historial de cambios de un submódulo del menú';

    protected string $description = 'Historial de cambios de un submódulo del menú';

    protected string $method = 'GET';

    protected string $path = '/navegation/modules/submodules/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de submódulo: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
