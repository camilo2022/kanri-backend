<?php

namespace App\Mcp\Prompts\Typology;

use App\Mcp\Prompts\RoutePrompt;

class TypologyDeletePrompt extends RoutePrompt
{
    protected string $name = 'typology_delete';

    protected string $title = 'Desactivar una tipología';

    protected string $description = 'Desactivar una tipología';

    protected string $method = 'DELETE';

    protected string $path = '/typification/typologies/delete/{id}';

    protected string $business = 'Elimina un registro de typology, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un typology. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
