<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserUpdatePrompt extends RoutePrompt
{
    protected string $name = 'user_update';

    protected string $title = 'Editar un usuario específico';

    protected string $description = 'Editar un usuario específico';

    protected string $method = 'PUT';

    protected string $path = '/users/update/{id}';

    protected string $business = 'Cambia el empleado asociado a un usuario y, opcionalmente, su contraseña. La contraseña solo se cambia si se envía una nueva; si se cambia, se cierran todas sus sesiones activas (se borran sus tokens) y deberá volver a iniciar sesión. Se rechaza si el empleado no existe, está desactivado o ya tiene otro usuario. No se regenera el nombre de usuario ni se actualizan sus roles o permisos al cambiar de empleado: no se encontró código que lo haga, por lo que puede quedar con los permisos del cargo anterior. Queda en la auditoría. La IA debe confirmar el cambio con mucho cuidado, porque afecta quién usa la cuenta y puede cerrar sesiones. La contraseña anterior no se recupera.';
}
