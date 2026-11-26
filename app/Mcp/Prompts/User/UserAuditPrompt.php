<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserAuditPrompt extends RoutePrompt
{
    protected string $name = 'user_audit';

    protected string $title = 'Historial de cambios de un usuario';

    protected string $description = 'Historial de cambios de un usuario';

    protected string $method = 'GET';

    protected string $path = '/users/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de usuario específico: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
