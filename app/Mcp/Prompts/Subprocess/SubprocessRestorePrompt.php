<?php

namespace App\Mcp\Prompts\Subprocess;

use App\Mcp\Prompts\RoutePrompt;

class SubprocessRestorePrompt extends RoutePrompt
{
    protected string $name = 'subprocess_restore';

    protected string $title = 'Restaurar un subproceso';

    protected string $description = 'Restaurar un subproceso';

    protected string $method = 'PATCH';

    protected string $path = '/workflow/processes/subprocesses/restore/{id}';

    protected string $business = 'Reactiva un subproceso desactivado. Se rechaza si no existe o no está desactivado. Efecto: quita la fecha de eliminación y vuelve al catálogo con su configuración, relación con el proceso y siguientes; no revalida nombre ni color únicos, por lo que no se pudo confirmar qué pasa si otro elemento tomó ese nombre o color. Vuelve a aparecer como puesto del proceso para programar y trabajar. La IA debe confirmar cuál subproceso reactivar. Es reversible con la ruta de eliminar subproceso.';
}
