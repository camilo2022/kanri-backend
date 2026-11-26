<?php

namespace App\Mcp\Prompts\StopType;

use App\Mcp\Prompts\RoutePrompt;

class StopTypeRestorePrompt extends RoutePrompt
{
    protected string $name = 'stop_type_restore';

    protected string $title = 'Restaurar un tipo de parada desactivado';

    protected string $description = 'Restaurar un tipo de parada desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/stop_types/restore/{id}';

    protected string $business = 'Reactiva un registro de stop type que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un stop type. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
