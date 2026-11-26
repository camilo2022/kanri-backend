<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserAssignPrompt extends RoutePrompt
{
    protected string $name = 'user_assign';

    protected string $title = 'Asignar un permiso a un usuario específio';

    protected string $description = 'Asignar un permiso a un usuario específio';

    protected string $method = 'POST';

    protected string $path = '/users/authorization/assign/{id}';

    protected string $business = 'Otorga un permiso a un usuario. CAMBIA LO QUE ESA PERSONA PUEDE HACER EN KANRI. Se rechaza si el usuario o el permiso no existen o si el usuario ya tiene ese permiso. Efectos: se agrega el permiso al usuario y, además, se le asignan automáticamente los roles a los que pertenece ese permiso si aún no los tenía. Esos roles no entregan permisos adicionales por sí solos; no se pudo confirmar si algún otro código los usa. Queda en la auditoría. La IA nunca debe otorgar permisos por iniciativa propia: solo si la persona lo pidió expresamente, y debe confirmar con mucho cuidado el usuario exacto, el permiso exacto (nombre y título) y su alcance. Es reversible con la ruta de quitar permiso.';
}
