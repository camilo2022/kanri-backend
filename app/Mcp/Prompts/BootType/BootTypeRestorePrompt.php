<?php

namespace App\Mcp\Prompts\BootType;

use App\Mcp\Prompts\RoutePrompt;

class BootTypeRestorePrompt extends RoutePrompt
{
    protected string $name = 'boot_type_restore';

    protected string $title = 'Restaurar un tipo de bota desactivado';

    protected string $description = 'Restaurar un tipo de bota desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/boot_types/restore/{id}';

    protected string $business = 'Reactiva un registro de boot type que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un boot type. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
