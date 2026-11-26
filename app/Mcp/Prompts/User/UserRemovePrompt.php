<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserRemovePrompt extends RoutePrompt
{
    protected string $name = 'user_remove';

    protected string $title = 'Remover un permiso a un usuario específico';

    protected string $description = 'Remover un permiso a un usuario específico';

    protected string $method = 'POST';

    protected string $path = '/users/authorization/remove/{id}';

    protected string $business = 'Quita un permiso a un usuario. CAMBIA LO QUE ESA PERSONA PUEDE HACER EN KANRI: puede perder acceso a pantallas o acciones de inmediato. Se rechaza si el usuario o el permiso no existen o si el usuario no tiene ese permiso asignado directamente. Efecto adicional: después de quitarlo, se retiran los roles del usuario cuyos permisos ya no tenga ninguno. El permiso se retira solo de este usuario, no del catálogo ni de otros. Queda en la auditoría. La IA debe confirmar con mucho cuidado el usuario y el permiso exactos, explicar qué dejará de poder hacer y no ejecutarlo por iniciativa propia. Es reversible volviendo a asignar el permiso.';
}
