<?php

namespace App\Mcp\Prompts\DocumentType;

use App\Mcp\Prompts\RoutePrompt;

class DocumentTypeSettingsPrompt extends RoutePrompt
{
    protected string $name = 'document_type_settings';

    protected string $title = 'Configurar las reglas del número de documento';

    protected string $description = 'Configurar las reglas del número de documento';

    protected string $method = 'PUT';

    protected string $path = '/typification/person_types/document_types/settings/{id}';

    protected string $business = 'Reemplaza la configuración de un tipo de documento. La clave es validations: lista (mínimo una) de reglas del número de documento; cada una con id, expresión regular, mensaje, fecha de creación y, opcionalmente, de actualización y de eliminación. El sistema las usa al registrar personas con ese tipo de documento: toma las validaciones activas (sin fecha de eliminación) e informa el mensaje de la primera que no se cumpla; una validación con deleted_at queda desactivada pero se conserva. Se RECHAZA si la regex no es válida o se repite, si un id se repite o falta algún campo obligatorio. No se confirmó en esta ruta que el tipo de documento exista antes de guardar. Confirma la lista completa, porque sustituye la anterior. Es reversible reenviando la previa.';
}
