<?php

namespace App\Mcp\Prompts\Process;

use App\Mcp\Prompts\RoutePrompt;

class ProcessUpdatePrompt extends RoutePrompt
{
    protected string $name = 'process_update';

    protected string $title = 'Editar un proceso';

    protected string $description = 'Editar un proceso';

    protected string $method = 'PUT';

    protected string $path = '/workflow/processes/update/{id}';

    protected string $business = 'Edita nombre, descripción, procesos posteriores y settings de un proceso. Aplica las mismas validaciones que crearlo (nombre y color únicos, un solo lugar de producción, medición obligatoria si va en la orden). El schema (tablas de la ficha) no se maneja aquí: se conserva el guardado desde configuración. Efecto: reemplaza el enlace de procesos posteriores y, si el flujo de subprocesos deja de ser sequence, borra las cadenas entre sus subprocesos. Cambiar in_production_order, productivity_measurement, any_workstation o los posteriores altera quién es el primer proceso, la medición de sus puestos y qué puestos trabajan cada referencia, así que afecta programación futura. Responde 201 en lugar de 200. La IA debe confirmar los cambios y avisar de ese impacto. Reversible editando de nuevo, salvo las cadenas borradas.';
}
