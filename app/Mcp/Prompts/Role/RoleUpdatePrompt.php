<?php

namespace App\Mcp\Prompts\Role;

use App\Mcp\Prompts\RoutePrompt;

class RoleUpdatePrompt extends RoutePrompt
{
    protected string $name = 'role_update';

    protected string $title = 'Editar un rol';

    protected string $description = 'Editar un rol';

    protected string $method = 'PUT';

    protected string $path = '/authorization/roles/update/{id}';

    protected string $business = 'Edita el nombre técnico, el título y la descripción de un rol existente. Se usa para corregir o renombrar un rol. Se rechaza si el rol no existe o si el nombre, el título o la descripción ya los usa otro rol. No cambia los permisos del rol ni a quién está asignado; sin embargo, el nombre del rol es un identificador técnico, y no se pudo confirmar si alguna otra parte del sistema lo usa como referencia, por lo que renombrarlo debe hacerse con cuidado. Queda en la auditoría. Antes de ejecutarlo, la IA debe mostrar el valor actual frente al nuevo y pedir confirmación. Es reversible volviendo a editar con los valores anteriores.';
}
