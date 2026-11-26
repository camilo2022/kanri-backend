<?php

namespace App\Mcp\Prompts\FileSubtype;

use App\Mcp\Prompts\RoutePrompt;

class FileSubtypeAllPrompt extends RoutePrompt
{
    protected string $name = 'file_subtype_all';

    protected string $title = 'Listar los subtipos de un tipo de archivo';

    protected string $description = 'Listar los subtipos de un tipo de archivo';

    protected string $method = 'GET';

    protected string $path = '/typification/file_types/file_subtypes/all/{file_type_id}';

    protected string $business = 'Devuelve la lista de subtipos de un tipo de archivo registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar subtipos de un tipo de archivo, o cuando necesites el id de uno para otra acción.

Notas: Solo devuelve subtipos vinculados a ese tipo de archivo. En esta lista no vienen `item_id` ni `file_type`. Un tipo de archivo inexistente devuelve una lista vacía.';
}
