<?php

namespace App\Mcp\Prompts\DocumentType;

use App\Mcp\Prompts\RoutePrompt;

class DocumentTypeDeletePrompt extends RoutePrompt
{
    protected string $name = 'document_type_delete';

    protected string $title = 'Desactivar un tipo de documento';

    protected string $description = 'Desactivar un tipo de documento';

    protected string $method = 'DELETE';

    protected string $path = '/typification/person_types/document_types/delete/{id}';

    protected string $business = 'Elimina (borrado lógico) un tipo de documento. Se RECHAZA si no existe o ya está eliminado. En el código revisado no hay verificación de dependencias: no se comprobó si hay personas registradas con ese tipo de documento, así que no se puede asegurar que el sistema lo impida. Antes de ejecutarlo, confirma con la persona el nombre del tipo de documento. Es reversible con la ruta de restauración, que conserva sus validaciones y su tipo de persona.';
}
