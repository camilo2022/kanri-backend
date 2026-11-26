<?php

namespace App\Mcp\Prompts\Subprocess;

use App\Mcp\Prompts\RoutePrompt;

class SubprocessDeletePrompt extends RoutePrompt
{
    protected string $name = 'subprocess_delete';

    protected string $title = 'Desactivar un subproceso';

    protected string $description = 'Desactivar un subproceso';

    protected string $method = 'DELETE';

    protected string $path = '/workflow/processes/subprocesses/delete/{id}';

    protected string $business = 'Desactiva (borrado lógico) un subproceso. El Request solo comprueba que exista y esté activo; no se encontró validación de operaciones, cadenas, fichas técnicas ni turnos que dependan de él, así que no se pudo confirmar el impacto. Efecto: sale del catálogo y de las listas de puestos del proceso; las flechas de la cadena y los registros históricos no se modifican en este código. Queda auditado. La IA debe confirmar el nombre y proceso del subproceso y advertir que el puesto deja de poder programarse. Es reversible con la ruta de restaurar subproceso.';
}
