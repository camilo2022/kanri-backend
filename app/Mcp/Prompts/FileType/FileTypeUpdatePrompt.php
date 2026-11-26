<?php

namespace App\Mcp\Prompts\FileType;

use App\Mcp\Prompts\RoutePrompt;

class FileTypeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'file_type_update';

    protected string $title = 'Editar un tipo de archivo';

    protected string $description = 'Editar un tipo de archivo';

    protected string $method = 'PUT';

    protected string $path = '/typification/file_types/update/{id}';

    protected string $business = 'Modifica un registro existente de file type, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un file type. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.';
}
