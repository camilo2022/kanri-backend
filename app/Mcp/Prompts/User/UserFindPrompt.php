<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserFindPrompt extends RoutePrompt
{
    protected string $name = 'user_find';

    protected string $title = 'Obtener un usuario específico';

    protected string $description = 'Obtener un usuario específico';

    protected string $method = 'GET';

    protected string $path = '/users/find/{id}';

    protected string $business = 'Muestra un usuario concreto con todo lo que puede hacer. Úsala cuando pregunten por el acceso de alguien puntual: su nombre de usuario, su empleado, su cargo, sus roles y permisos. Además de su empleado (con persona, cargo y área), roles y permisos, trae los procesos, subprocesos y operaciones de producción que tiene asignados. No trae contraseñas. Es solo consulta; si no existe responde que no se encontró.';
}
