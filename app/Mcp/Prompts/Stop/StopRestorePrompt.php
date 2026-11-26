<?php

namespace App\Mcp\Prompts\Stop;

use App\Mcp\Prompts\RoutePrompt;

class StopRestorePrompt extends RoutePrompt
{
    protected string $name = 'stop_restore';

    protected string $title = 'Restaurar una parada desactivada';

    protected string $description = 'Restaurar una parada desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/typification/stop_types/stops/restore/{id}';

    protected string $business = 'Reactiva un registro de stop que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un stop. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
