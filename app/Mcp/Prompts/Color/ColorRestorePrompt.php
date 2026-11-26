<?php

namespace App\Mcp\Prompts\Color;

use App\Mcp\Prompts\RoutePrompt;

class ColorRestorePrompt extends RoutePrompt
{
    protected string $name = 'color_restore';

    protected string $title = 'Restaurar un color desactivado';

    protected string $description = 'Restaurar un color desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/colors/restore/{id}';

    protected string $business = 'Reactiva un registro de color que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un color. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
