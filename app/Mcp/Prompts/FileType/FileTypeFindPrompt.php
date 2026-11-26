<?php

namespace App\Mcp\Prompts\FileType;

use App\Mcp\Prompts\RoutePrompt;

class FileTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'file_type_find';

    protected string $title = 'Obtener un tipo de archivo';

    protected string $description = 'Obtener un tipo de archivo';

    protected string $method = 'GET';

    protected string $path = '/typification/file_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de archivo por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.

Notas: No incluye `file_subtypes`: los subtipos tienen su propia lista.';
}
