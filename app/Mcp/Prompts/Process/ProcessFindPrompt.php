<?php

namespace App\Mcp\Prompts\Process;

use App\Mcp\Prompts\RoutePrompt;

class ProcessFindPrompt extends RoutePrompt
{
    protected string $name = 'process_find';

    protected string $title = 'Obtener un proceso';

    protected string $description = 'Obtener un proceso';

    protected string $method = 'GET';

    protected string $path = '/workflow/processes/find/{id}';

    protected string $business = 'Trae un proceso de la ruta de producción por su id numérico, con su ítem, los procesos anteriores y siguientes y sus subprocesos con sus operaciones. Úsala cuando la persona pida el detalle de un proceso, por ejemplo «¿qué subprocesos y operaciones tiene Lavandería?». Un proceso desactivado no se encuentra. No muestra los lotes que están en ese proceso ni su productividad.';
}
