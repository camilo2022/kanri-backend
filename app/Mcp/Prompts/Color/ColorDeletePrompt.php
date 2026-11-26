<?php

namespace App\Mcp\Prompts\Color;

use App\Mcp\Prompts\RoutePrompt;

class ColorDeletePrompt extends RoutePrompt
{
    protected string $name = 'color_delete';

    protected string $title = 'Desactivar un color';

    protected string $description = 'Desactivar un color';

    protected string $method = 'DELETE';

    protected string $path = '/colors/delete/{id}';

    protected string $business = 'Elimina un registro de color, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un color. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
