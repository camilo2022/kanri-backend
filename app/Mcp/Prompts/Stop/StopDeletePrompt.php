<?php

namespace App\Mcp\Prompts\Stop;

use App\Mcp\Prompts\RoutePrompt;

class StopDeletePrompt extends RoutePrompt
{
    protected string $name = 'stop_delete';

    protected string $title = 'Desactivar una parada';

    protected string $description = 'Desactivar una parada';

    protected string $method = 'DELETE';

    protected string $path = '/typification/stop_types/stops/delete/{id}';

    protected string $business = 'Elimina un registro de stop, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un stop. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
