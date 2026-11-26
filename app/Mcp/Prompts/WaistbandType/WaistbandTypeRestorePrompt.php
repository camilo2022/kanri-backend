<?php

namespace App\Mcp\Prompts\WaistbandType;

use App\Mcp\Prompts\RoutePrompt;

class WaistbandTypeRestorePrompt extends RoutePrompt
{
    protected string $name = 'waistband_type_restore';

    protected string $title = 'Restaurar un tipo de pretina desactivado';

    protected string $description = 'Restaurar un tipo de pretina desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/waistband_types/restore/{id}';

    protected string $business = 'Reactiva un registro de waistband type que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un waistband type. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
