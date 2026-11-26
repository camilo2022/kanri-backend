<?php

namespace App\Mcp\Prompts\Operation;

use App\Mcp\Prompts\RoutePrompt;

class OperationStorePrompt extends RoutePrompt
{
    protected string $name = 'operation_store';

    protected string $title = 'Crear una operación';

    protected string $description = 'Crear una operación';

    protected string $method = 'POST';

    protected string $path = '/workflow/processes/subprocesses/operations/store';

    protected string $business = 'Crea una operación dentro de un subproceso (subprocess_id). Se rechaza si el nombre o el color ya existen; si las siguientes operaciones o dependencias (before_operations) no son hermanas, el subproceso no está en la secuencia o paralelo que corresponde, o hay ciclos; o si los subprocesos siguientes no son del mismo proceso en secuencia. Claves de settings: in_technical_sheet, in_production_order, productivity_measurement, time_multiplier (none, buttons, manual con etiqueta), units_entry, schedule_units; lo no definido se hereda del subproceso. Efecto: vincula al subproceso, crea la cadena y las dependencias «depende de» en las operaciones de las que depende, y los subprocesos siguientes (una operación que abre una línea, como RESINA). La IA debe confirmar subproceso, relaciones y reglas. Reversible: se edita o desactiva.';
}
