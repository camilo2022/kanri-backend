<?php

namespace App\Mcp\Prompts\Operation;

use App\Mcp\Prompts\RoutePrompt;

class OperationUpdatePrompt extends RoutePrompt
{
    protected string $name = 'operation_update';

    protected string $title = 'Editar una operación';

    protected string $description = 'Editar una operación';

    protected string $method = 'PUT';

    protected string $path = '/workflow/processes/subprocesses/operations/update/{id}';

    protected string $business = 'Edita una operación con las mismas validaciones que crearla (nombre y color únicos; siguientes, dependencias y subprocesos siguientes coherentes con el flujo del subproceso y sin ciclos). El schema no se edita aquí: se conserva. Efecto: reemplaza solo las relaciones que se envían (after_operations, after_subprocesses, before_operations); «depende de» se agrega o quita en la operación de la que depende. Las claves de settings cambian reglas: productivity_measurement, time_multiplier, units_entry y schedule_units sobrescriben lo heredado del subproceso; in_technical_sheet decide si la ficha debe marcarla. Efecto en programación y conteo de turnos futuros. La IA debe confirmar los cambios. Reversible editando de nuevo.';
}
