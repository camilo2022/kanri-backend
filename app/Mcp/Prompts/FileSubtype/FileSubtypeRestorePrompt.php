<?php

namespace App\Mcp\Prompts\FileSubtype;

use App\Mcp\Prompts\RoutePrompt;

class FileSubtypeRestorePrompt extends RoutePrompt
{
    protected string $name = 'file_subtype_restore';

    protected string $title = 'Restaurar un subtipo de archivo desactivado';

    protected string $description = 'Restaurar un subtipo de archivo desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/file_types/file_subtypes/restore/{id}';

    protected string $business = 'Reactiva un registro de file subtype que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un file subtype. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
