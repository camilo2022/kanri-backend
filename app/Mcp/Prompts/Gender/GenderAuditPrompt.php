<?php

namespace App\Mcp\Prompts\Gender;

use App\Mcp\Prompts\RoutePrompt;

class GenderAuditPrompt extends RoutePrompt
{
    protected string $name = 'gender_audit';

    protected string $title = 'Historial de cambios de un género';

    protected string $description = 'Historial de cambios de un género';

    protected string $method = 'GET';

    protected string $path = '/genders/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de género: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
