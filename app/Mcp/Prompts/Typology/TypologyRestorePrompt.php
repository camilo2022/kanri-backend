<?php

namespace App\Mcp\Prompts\Typology;

use App\Mcp\Prompts\RoutePrompt;

class TypologyRestorePrompt extends RoutePrompt
{
    protected string $name = 'typology_restore';

    protected string $title = 'Restaurar una tipología desactivada';

    protected string $description = 'Restaurar una tipología desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/typification/typologies/restore/{id}';

    protected string $business = 'Reactiva un registro de typology que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un typology. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
