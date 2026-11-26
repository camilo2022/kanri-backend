<?php

namespace App\Mcp\Prompts\FileType;

use App\Mcp\Prompts\RoutePrompt;

class FileTypeRestorePrompt extends RoutePrompt
{
    protected string $name = 'file_type_restore';

    protected string $title = 'Restaurar un tipo de archivo desactivado';

    protected string $description = 'Restaurar un tipo de archivo desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/file_types/restore/{id}';

    protected string $business = 'Reactiva un registro de file type que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un file type. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
