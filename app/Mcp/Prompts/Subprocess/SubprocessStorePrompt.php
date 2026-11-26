<?php

namespace App\Mcp\Prompts\Subprocess;

use App\Mcp\Prompts\RoutePrompt;

class SubprocessStorePrompt extends RoutePrompt
{
    protected string $name = 'subprocess_store';

    protected string $title = 'Crear un subproceso';

    protected string $description = 'Crear un subproceso';

    protected string $method = 'POST';

    protected string $path = '/workflow/processes/subprocesses/store';

    protected string $business = 'Crea un subproceso dentro de un proceso (process_id), con descripción en mayúsculas y subprocesos siguientes. Se rechaza si el nombre o el color ya existen, si los siguientes no son hermanos del mismo proceso o el proceso no tiene sus subprocesos en secuencia, si una condición de tono apunta a un subproceso que no es siguiente o no trae tonos ni regreso. Claves de settings: operations y operation_flow, in_technical_sheet, in_production_order, productivity_measurement, time_multiplier (none, buttons o manual con etiqueta), units_entry (plus o finish), schedule_units (auto o typed), only_after_previous y next_conditions (tonos de lavado, except, returns). Efecto: vincula al proceso y crea las flechas de la cadena. La IA debe confirmar proceso, siguientes y reglas. Reversible: se edita o desactiva.';
}
