<?php

namespace App\Mcp\Prompts\FileSubtype;

use App\Mcp\Prompts\RoutePrompt;

class FileSubtypeDeletePrompt extends RoutePrompt
{
    protected string $name = 'file_subtype_delete';

    protected string $title = 'Desactivar un subtipo de archivo';

    protected string $description = 'Desactivar un subtipo de archivo';

    protected string $method = 'DELETE';

    protected string $path = '/typification/file_types/file_subtypes/delete/{id}';

    protected string $business = 'Elimina un registro de file subtype, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un file subtype. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
