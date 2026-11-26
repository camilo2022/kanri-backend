<?php

namespace App\Mcp\Prompts\Subprocess;

use App\Mcp\Prompts\RoutePrompt;

class SubprocessAllPrompt extends RoutePrompt
{
    protected string $name = 'subprocess_all';

    protected string $title = 'Listar los subprocesos de un proceso';

    protected string $description = 'Listar los subprocesos de un proceso';

    protected string $method = 'GET';

    protected string $path = '/workflow/processes/subprocesses/all/{process_id?}';

    protected string $business = 'Lista los subprocesos, que son las etapas internas de un proceso (por ejemplo dentro de Confección). Con process_id en la ruta trae solo los de ese proceso; sin él trae todos. Cada subproceso trae su ítem, su proceso, y los subprocesos anteriores y siguientes. Úsala cuando la persona pregunte «¿qué subprocesos tiene el proceso X?». Admite búsqueda, orden y paginación; sin per_page devuelve todos; with_trashed es obligatorio e incluye los desactivados. No trae las operaciones de cada subproceso: para eso se usa la lista de operaciones de un subproceso.';
}
