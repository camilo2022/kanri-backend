<?php

namespace App\Mcp\Prompts\FileSubtype;

use App\Mcp\Prompts\RoutePrompt;

class FileSubtypeFindPrompt extends RoutePrompt
{
    protected string $name = 'file_subtype_find';

    protected string $title = 'Obtener un subtipo de archivo';

    protected string $description = 'Obtener un subtipo de archivo';

    protected string $method = 'GET';

    protected string $path = '/typification/file_types/file_subtypes/find/{id}';

    protected string $business = 'Trae un solo registro de subtipo de archivo por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
