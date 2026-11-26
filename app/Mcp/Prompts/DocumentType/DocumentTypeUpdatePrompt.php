<?php

namespace App\Mcp\Prompts\DocumentType;

use App\Mcp\Prompts\RoutePrompt;

class DocumentTypeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'document_type_update';

    protected string $title = 'Editar un tipo de documento';

    protected string $description = 'Editar un tipo de documento';

    protected string $method = 'PUT';

    protected string $path = '/typification/person_types/document_types/update/{id}';

    protected string $business = 'Edita el nombre y la descripción de un tipo de documento. No cambia el tipo de persona asociado ni sus validaciones de formato. Se RECHAZA si el tipo de documento no existe, si nombre o descripción no vienen en MAYÚSCULAS (uppercase rechaza, no convierte), si el nombre tiene menos de 4 caracteres o coincide con otro tipo de documento (activo o eliminado). Confirma con la persona cuál tipo de documento se cambia y los textos nuevos. Es reversible volviendo a editar; el historial queda en la auditoría.';
}
