<?php

namespace App\Mcp\Prompts\FileType;

use App\Mcp\Prompts\RoutePrompt;

class FileTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'file_type_all';

    protected string $title = 'Listar los tipos de archivo';

    protected string $description = 'Listar los tipos de archivo';

    protected string $method = 'GET';

    protected string $path = '/typification/file_types/all';

    protected string $business = 'Devuelve la lista de tipos de archivo registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar tipos de archivo, o cuando necesites el id de uno para otra acción.

Notas: Cada tipo de archivo trae sus subtipos. En esta lista no viene `item_id`.';
}
