<?php

namespace App\Mcp\Prompts\Operation;

use App\Mcp\Prompts\RoutePrompt;

class OperationFindPrompt extends RoutePrompt
{
    protected string $name = 'operation_find';

    protected string $title = 'Obtener una operación';

    protected string $description = 'Obtener una operación';

    protected string $method = 'GET';

    protected string $path = '/workflow/processes/subprocesses/operations/find/{id}';

    protected string $business = 'Trae un solo registro de operación por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.

Notas: Incluye el catálogo `model_types` para configurar el formulario.';
}
