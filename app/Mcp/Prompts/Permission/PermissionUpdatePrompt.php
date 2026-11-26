<?php

namespace App\Mcp\Prompts\Permission;

use App\Mcp\Prompts\RoutePrompt;

class PermissionUpdatePrompt extends RoutePrompt
{
    protected string $name = 'permission_update';

    protected string $title = 'Editar un permiso';

    protected string $description = 'Editar un permiso';

    protected string $method = 'PUT';

    protected string $path = '/authorization/permissions/update/{id}';

    protected string $business = 'Edita el nombre técnico, el título, la descripción y el rol de un permiso existente. Cambiar el rol reemplaza la asociación anterior (el permiso queda ligado solo al rol nuevo). Cambiar el «nombre» es delicado: es el texto que las rutas comparan para autorizar, así que si se renombra un permiso que una ruta ya exige con el nombre anterior, esa ruta podría quedar bloqueada para quienes lo tenían; no se pudo confirmar qué rutas dependen de cada nombre. Se rechaza si el permiso o el rol no existen, o si el nombre, el título o la descripción coinciden con los de otro permiso. Queda en la auditoría. Antes de ejecutarlo, la IA debe mostrar el valor actual contra el nuevo y pedir confirmación expresa. Es reversible volviendo a editar con los valores anteriores.';
}
