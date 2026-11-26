<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserRestorePrompt extends RoutePrompt
{
    protected string $name = 'user_restore';

    protected string $title = 'Activar un usuario específico';

    protected string $description = 'Activar un usuario específico';

    protected string $method = 'PATCH';

    protected string $path = '/users/restore/{id}';

    protected string $business = 'Reactiva un usuario desactivado: vuelve a poder entrar a Kanri con los mismos roles, permisos y asignaciones que tenía, porque la desactivación no los borra. Se rechaza si el usuario no existe o no está desactivado. No verifica que su empleado siga activo; no se pudo confirmar ese caso. Como devuelve capacidades de acceso, la IA debe confirmar con cuidado quién es y qué podrá hacer. Queda en la auditoría. Es reversible: se puede volver a desactivar.';
}
