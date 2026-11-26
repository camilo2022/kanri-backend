<?php

namespace App\Mcp\Prompts\Operation;

use App\Mcp\Prompts\RoutePrompt;

class OperationRestorePrompt extends RoutePrompt
{
    protected string $name = 'operation_restore';

    protected string $title = 'Restaurar una operación desactivada';

    protected string $description = 'Restaurar una operación desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/workflow/processes/subprocesses/operations/restore/{id}';

    protected string $business = 'Reactiva una operación desactivada. Se rechaza si no existe o no está desactivada. Efecto: quita la fecha de eliminación y vuelve al catálogo con su configuración y relaciones; no revalida nombre ni color únicos, así que no se pudo confirmar qué ocurre si otro elemento tomó ese nombre o color. Vuelve a poder programarse y trabajarse. La IA debe confirmar cuál operación reactivar. Es reversible con la ruta de eliminar operación.';
}
