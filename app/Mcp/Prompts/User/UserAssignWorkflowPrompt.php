<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserAssignWorkflowPrompt extends RoutePrompt
{
    protected string $name = 'user_assign_workflow';

    protected string $title = 'Asignar un proceso, subproceso u operación a un usuario';

    protected string $description = 'Asignar un proceso, subproceso u operación a un usuario';

    protected string $method = 'POST';

    protected string $path = '/users/workflow/assign/{id}';

    protected string $business = 'Asigna a un usuario elementos del flujo de producción: un proceso, un subproceso o una operación. Se usa para definir en qué partes de la producción puede trabajar esa persona. El tipo indica qué se asigna, con el nombre de la clase del modelo (proceso, subproceso u operación) y el identificador del elemento. Efecto en cascada: asignar un proceso asigna también todos sus subprocesos y operaciones; asignar un subproceso agrega su proceso y todas sus operaciones; asignar una operación agrega también su subproceso y su proceso. Lo que el usuario ya tenía no se duplica. Se rechaza si el usuario no existe o el tipo no es válido; el identificador del elemento no se valida en el Request y uno inexistente da error de no encontrado. Queda en la auditoría. La IA debe confirmar usuario y elemento y avisar de la cascada. Es reversible con la ruta de quitar flujo.';
}
