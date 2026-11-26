<?php

namespace App\Mcp\Prompts\FileSubtype;

use App\Mcp\Prompts\RoutePrompt;

class FileSubtypeAuditPrompt extends RoutePrompt
{
    protected string $name = 'file_subtype_audit';

    protected string $title = 'Historial de cambios de un subtipo de archivo';

    protected string $description = 'Historial de cambios de un subtipo de archivo';

    protected string $method = 'GET';

    protected string $path = '/typification/file_types/file_subtypes/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de subtipo de archivo: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
