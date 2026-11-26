<?php

namespace App\Mcp\Prompts\Operation;

use App\Mcp\Prompts\RoutePrompt;

class OperationAllPrompt extends RoutePrompt
{
    protected string $name = 'operation_all';

    protected string $title = 'Listar las operaciones de un subproceso';

    protected string $description = 'Listar las operaciones de un subproceso';

    protected string $method = 'GET';

    protected string $path = '/workflow/processes/subprocesses/operations/all/{subprocess_id}';

    protected string $business = 'Lista las operaciones de un subproceso (los pasos concretos de trabajo, como pegar bolsillo o cerrar costado), identificado por su id numérico en la ruta. Cada operación trae su subproceso, las operaciones anteriores y siguientes y los subprocesos siguientes. Úsala cuando la persona pregunte «¿qué operaciones tiene el subproceso X?». Admite búsqueda, orden y paginación; sin per_page devuelve todas; with_trashed es obligatorio e incluye las desactivadas (también para el subproceso). Un subproceso inexistente devuelve una lista vacía, no un error. No trae tiempos ni reglas estándar de cada operación.';
}
