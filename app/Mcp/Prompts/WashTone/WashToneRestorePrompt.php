<?php

namespace App\Mcp\Prompts\WashTone;

use App\Mcp\Prompts\RoutePrompt;

class WashToneRestorePrompt extends RoutePrompt
{
    protected string $name = 'wash_tone_restore';

    protected string $title = 'Restaurar un tono de lavado desactivado';

    protected string $description = 'Restaurar un tono de lavado desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/wash_tones/restore/{id}';

    protected string $business = 'Reactiva un registro de wash tone que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un wash tone. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
