<?php

namespace App\Mcp\Prompts\Process;

use App\Mcp\Prompts\RoutePrompt;

class ProcessAllPrompt extends RoutePrompt
{
    protected string $name = 'process_all';

    protected string $title = 'Listar los procesos';

    protected string $description = 'Listar los procesos';

    protected string $method = 'GET';

    protected string $path = '/workflow/processes/all';

    protected string $business = 'Lista los procesos de la ruta de producción (por ejemplo Corte, Confección, Lavandería), con su ítem, los procesos anteriores y siguientes y, anidados, sus subprocesos con sus operaciones y las relaciones de orden de cada uno. Úsala cuando la persona pregunte «¿qué procesos tiene el flujo?», «¿qué subprocesos tiene Confección?» o «¿qué procesos van en la ficha técnica?». Filtros propios: in_technical_sheet e in_production_order (procesos que aplican a la ficha o a la orden de producción), with_typologies (agrega las tipologías de cada proceso), process_id (excluye ese proceso, útil para elegir anteriores o siguientes) y with_trashed_children (incluye subprocesos y operaciones desactivados). with_trashed es obligatorio. Admite búsqueda, orden y paginación; sin per_page devuelve todos. No muestra qué lotes están en cada proceso.';
}
