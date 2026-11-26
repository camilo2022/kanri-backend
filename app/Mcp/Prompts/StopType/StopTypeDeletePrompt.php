<?php

namespace App\Mcp\Prompts\StopType;

use App\Mcp\Prompts\RoutePrompt;

class StopTypeDeletePrompt extends RoutePrompt
{
    protected string $name = 'stop_type_delete';

    protected string $title = 'Desactivar un tipo de parada';

    protected string $description = 'Desactivar un tipo de parada';

    protected string $method = 'DELETE';

    protected string $path = '/typification/stop_types/delete/{id}';

    protected string $business = 'Elimina un registro de stop type, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un stop type. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
