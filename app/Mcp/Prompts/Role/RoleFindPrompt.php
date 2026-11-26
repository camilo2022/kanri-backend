<?php

namespace App\Mcp\Prompts\Role;

use App\Mcp\Prompts\RoutePrompt;

class RoleFindPrompt extends RoutePrompt
{
    protected string $name = 'role_find';

    protected string $title = 'Obtener un rol';

    protected string $description = 'Obtener un rol';

    protected string $method = 'GET';

    protected string $path = '/authorization/roles/find/{id}';

    protected string $business = 'Muestra un rol concreto junto con todos sus permisos. Úsala cuando la persona quiera ver qué permisos incluye un rol puntual y ya se conoce su id. Es solo consulta; si el rol no existe responde que no se encontró.';
}
