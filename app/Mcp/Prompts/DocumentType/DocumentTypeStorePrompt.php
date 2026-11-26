<?php

namespace App\Mcp\Prompts\DocumentType;

use App\Mcp\Prompts\RoutePrompt;

class DocumentTypeStorePrompt extends RoutePrompt
{
    protected string $name = 'document_type_store';

    protected string $title = 'Crear un tipo de documento';

    protected string $description = 'Crear un tipo de documento';

    protected string $method = 'POST';

    protected string $path = '/typification/person_types/document_types/store';

    protected string $business = 'Crea un tipo de documento de identidad (por ejemplo cédula, NIT) y lo asocia a un tipo de persona (natural, jurídica, etc.), que debe existir. Nace sin validaciones de formato del número; se definen en la ruta de configuración. Se RECHAZA si el tipo de persona no existe, si nombre o descripción no están en MAYÚSCULAS (uppercase rechaza, no convierte), si el nombre tiene menos de 4 caracteres o ya existe otro tipo de documento con ese nombre (incluidos eliminados). Confirma con la persona nombre, descripción y tipo de persona. Es reversible: se edita o elimina. Nota: el vínculo con el tipo de persona se fija aquí; la edición posterior no lo cambia.';
}
