<?php

namespace App\Mcp\Prompts\WashTone;

use App\Mcp\Prompts\RoutePrompt;

class WashToneDeletePrompt extends RoutePrompt
{
    protected string $name = 'wash_tone_delete';

    protected string $title = 'Desactivar un tono de lavado';

    protected string $description = 'Desactivar un tono de lavado';

    protected string $method = 'DELETE';

    protected string $path = '/wash_tones/delete/{id}';

    protected string $business = 'Elimina un registro de wash tone, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un wash tone. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
