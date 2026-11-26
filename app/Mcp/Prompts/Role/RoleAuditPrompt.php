<?php

namespace App\Mcp\Prompts\Role;

use App\Mcp\Prompts\RoutePrompt;

class RoleAuditPrompt extends RoutePrompt
{
    protected string $name = 'role_audit';

    protected string $title = 'Historial de cambios de un rol';

    protected string $description = 'Historial de cambios de un rol';

    protected string $method = 'GET';

    protected string $path = '/authorization/roles/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de rol: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
