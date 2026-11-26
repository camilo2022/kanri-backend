<?php

namespace App\Mcp\Prompts\Typology;

use App\Mcp\Prompts\RoutePrompt;

class TypologyAuditPrompt extends RoutePrompt
{
    protected string $name = 'typology_audit';

    protected string $title = 'Historial de cambios de una tipología';

    protected string $description = 'Historial de cambios de una tipología';

    protected string $method = 'GET';

    protected string $path = '/typification/typologies/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de tipología: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
