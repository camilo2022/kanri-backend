<?php

namespace App\Mcp\Prompts\Permission;

use App\Mcp\Prompts\RoutePrompt;

class PermissionFindPrompt extends RoutePrompt
{
    protected string $name = 'permission_find';

    protected string $title = 'Obtener un permiso';

    protected string $description = 'Obtener un permiso';

    protected string $method = 'GET';

    protected string $path = '/authorization/permissions/find/{id}';

    protected string $business = 'Muestra un permiso concreto con el detalle de los roles que lo tienen. Úsala cuando la persona quiera saber qué hace un permiso o qué roles lo incluyen y ya se conoce su id. Es solo consulta; si el permiso no existe responde que no se encontró.';
}
