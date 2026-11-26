<?php

namespace App\Mcp\Prompts\FileSubtype;

use App\Mcp\Prompts\RoutePrompt;

class FileSubtypeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'file_subtype_update';

    protected string $title = 'Editar un subtipo de archivo';

    protected string $description = 'Editar un subtipo de archivo';

    protected string $method = 'PUT';

    protected string $path = '/typification/file_types/file_subtypes/update/{id}';

    protected string $business = 'Modifica un registro existente de file subtype, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un file subtype. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Solo cambia nombre y descripción: no cambia el tipo de archivo.';
}
