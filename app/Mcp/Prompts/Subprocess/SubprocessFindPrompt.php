<?php

namespace App\Mcp\Prompts\Subprocess;

use App\Mcp\Prompts\RoutePrompt;

class SubprocessFindPrompt extends RoutePrompt
{
    protected string $name = 'subprocess_find';

    protected string $title = 'Obtener un subproceso';

    protected string $description = 'Obtener un subproceso';

    protected string $method = 'GET';

    protected string $path = '/workflow/processes/subprocesses/find/{id}';

    protected string $business = 'Trae un subproceso por su id numérico, con su proceso y sus subprocesos anteriores y siguientes. Úsala cuando la persona pida el detalle de un subproceso concreto. Un subproceso desactivado no se encuentra. No trae sus operaciones (para eso está la lista de operaciones de un subproceso) ni los lotes que pasan por él.';
}
