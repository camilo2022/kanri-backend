<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserRemoveWorkflowPrompt extends RoutePrompt
{
    protected string $name = 'user_remove_workflow';

    protected string $title = 'Remover un proceso, subproceso u operación de un usuario';

    protected string $description = 'Remover un proceso, subproceso u operación de un usuario';

    protected string $method = 'POST';

    protected string $path = '/users/workflow/remove/{id}';

    protected string $business = 'Quita a un usuario un proceso, un subproceso o una operación del flujo de producción, con lo que deja de poder trabajar en ellos. Efecto en cascada hacia abajo: quitar un proceso retira también sus subprocesos y operaciones; quitar un subproceso retira sus operaciones; quitar una operación retira solo esa. No se quita el elemento padre al quitar un hijo, así que un usuario puede quedar con un proceso sin todos sus hijos. Se rechaza si el usuario o el elemento no existen, o si el elemento no está asignado al usuario. Queda en la auditoría. La IA debe confirmar usuario y elemento y explicar el efecto en cascada. Es reversible volviendo a asignarlo.';
}
