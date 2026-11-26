<?php

namespace App\Mcp\Prompts\Permission;

use App\Mcp\Prompts\RoutePrompt;

class PermissionAuditPrompt extends RoutePrompt
{
    protected string $name = 'permission_audit';

    protected string $title = 'Historial de cambios de un permiso';

    protected string $description = 'Historial de cambios de un permiso';

    protected string $method = 'GET';

    protected string $path = '/authorization/permissions/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de permiso: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
