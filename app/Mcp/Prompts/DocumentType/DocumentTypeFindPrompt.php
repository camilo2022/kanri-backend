<?php

namespace App\Mcp\Prompts\DocumentType;

use App\Mcp\Prompts\RoutePrompt;

class DocumentTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'document_type_find';

    protected string $title = 'Obtener un tipo de documento';

    protected string $description = 'Obtener un tipo de documento';

    protected string $method = 'GET';

    protected string $path = '/typification/person_types/document_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de documento por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
