<?php

namespace App\Mcp\Prompts\TechnicalSheet;

use App\Mcp\Prompts\RoutePrompt;

class TechnicalSheetAuditPrompt extends RoutePrompt
{
    protected string $name = 'technical_sheet_audit';

    protected string $title = 'Historial de cambios de una ficha técnica';

    protected string $description = 'Historial de cambios de una ficha técnica';

    protected string $method = 'GET';

    protected string $path = '/technical_sheets/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de ficha técnica: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
