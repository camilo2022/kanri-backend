<?php

namespace App\Mcp\Prompts\Operation;

use App\Mcp\Prompts\RoutePrompt;

class OperationDeletePrompt extends RoutePrompt
{
    protected string $name = 'operation_delete';

    protected string $title = 'Desactivar una operación';

    protected string $description = 'Desactivar una operación';

    protected string $method = 'DELETE';

    protected string $path = '/workflow/processes/subprocesses/operations/delete/{id}';

    protected string $business = 'Desactiva (borrado lógico) una operación. El Request solo verifica que exista y esté activa: no se encontró validación de dependencias en cadenas, fichas técnicas ni registros de trabajo, por lo que no se pudo confirmar su impacto. Efecto: la operación sale del catálogo y deja de ser puesto programable; las relaciones con otras operaciones y los registros históricos no se modifican en este código. Queda auditado. La IA debe confirmar nombre y subproceso, y advertir que fichas que la marcaban pueden quedar con una ruta distinta. Es reversible con la ruta de restaurar operación.';
}
