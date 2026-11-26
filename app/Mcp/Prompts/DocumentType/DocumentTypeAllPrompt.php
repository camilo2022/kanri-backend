<?php

namespace App\Mcp\Prompts\DocumentType;

use App\Mcp\Prompts\RoutePrompt;

class DocumentTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'document_type_all';

    protected string $title = 'Listar los tipos de documento de un tipo de persona';

    protected string $description = 'Listar los tipos de documento de un tipo de persona';

    protected string $method = 'GET';

    protected string $path = '/typification/person_types/document_types/all/{person_type_id}';

    protected string $business = 'Devuelve la lista de tipos de documento de un tipo de persona registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar tipos de documento de un tipo de persona, o cuando necesites el id de uno para otra acción.

Notas: En esta lista no vienen `item_id` ni `person_type`. Un tipo de persona inexistente devuelve una lista vacía.';
}
