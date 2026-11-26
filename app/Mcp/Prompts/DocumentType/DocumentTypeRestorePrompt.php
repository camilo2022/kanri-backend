<?php

namespace App\Mcp\Prompts\DocumentType;

use App\Mcp\Prompts\RoutePrompt;

class DocumentTypeRestorePrompt extends RoutePrompt
{
    protected string $name = 'document_type_restore';

    protected string $title = 'Restaurar un tipo de documento desactivado';

    protected string $description = 'Restaurar un tipo de documento desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/person_types/document_types/restore/{id}';

    protected string $business = 'Reactiva un tipo de documento eliminado, con nombre, descripción, tipo de persona y validaciones tal como estaban. Se RECHAZA si no existe o no está eliminado. No hay conflicto de nombre porque la unicidad contaba también a los eliminados. Confirma con la persona cuál tipo de documento recuperar. Es reversible: se puede volver a eliminar.';
}
