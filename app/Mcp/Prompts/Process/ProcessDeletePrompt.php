<?php

namespace App\Mcp\Prompts\Process;

use App\Mcp\Prompts\RoutePrompt;

class ProcessDeletePrompt extends RoutePrompt
{
    protected string $name = 'process_delete';

    protected string $title = 'Desactivar un proceso';

    protected string $description = 'Desactivar un proceso';

    protected string $method = 'DELETE';

    protected string $path = '/workflow/processes/delete/{id}';

    protected string $business = 'Desactiva (borrado lógico) un proceso del flujo. El Request solo comprueba que exista y esté activo: no se encontró validación de subprocesos, turnos, fichas técnicas u órdenes que dependan de él, por lo que no se pudo confirmar el impacto en datos existentes. Efecto: el proceso queda con fecha de eliminación, sale de los catálogos y de la línea de procesos usada por las reglas de producción (primer proceso, posteriores); queda auditado. La IA debe confirmar el nombre del proceso y advertir que puede afectar el flujo productivo y los turnos. Es reversible con la ruta de restaurar proceso.';
}
