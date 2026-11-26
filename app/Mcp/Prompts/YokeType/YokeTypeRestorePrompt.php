<?php

namespace App\Mcp\Prompts\YokeType;

use App\Mcp\Prompts\RoutePrompt;

class YokeTypeRestorePrompt extends RoutePrompt
{
    protected string $name = 'yoke_type_restore';

    protected string $title = 'Restaurar un tipo de cotilla desactivado';

    protected string $description = 'Restaurar un tipo de cotilla desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/yoke_types/restore/{id}';

    protected string $business = 'Reactiva un registro de yoke type que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un yoke type. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
