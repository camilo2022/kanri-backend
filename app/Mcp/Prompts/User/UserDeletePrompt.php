<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserDeletePrompt extends RoutePrompt
{
    protected string $name = 'user_delete';

    protected string $title = 'Desactivar un usuario específico';

    protected string $description = 'Desactivar un usuario específico';

    protected string $method = 'DELETE';

    protected string $path = '/users/delete/{id}';

    protected string $business = 'Desactiva un usuario (eliminación lógica): deja de poder entrar a Kanri, pero conserva sus roles, permisos y asignaciones, de modo que al reactivarlo vuelve como estaba. Se rechaza si el usuario no existe o ya está desactivado. En el código leído no se cierran explícitamente sus sesiones abiertas; no se pudo confirmar si el token vigente deja de funcionar de inmediato. No toca al empleado ni a la persona. Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar el usuario y pedir confirmación expresa. Es reversible con la restauración de usuarios.';
}
