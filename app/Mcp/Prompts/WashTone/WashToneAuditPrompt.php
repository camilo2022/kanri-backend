<?php

namespace App\Mcp\Prompts\WashTone;

use App\Mcp\Prompts\RoutePrompt;

class WashToneAuditPrompt extends RoutePrompt
{
    protected string $name = 'wash_tone_audit';

    protected string $title = 'Historial de cambios de un tono de lavado';

    protected string $description = 'Historial de cambios de un tono de lavado';

    protected string $method = 'GET';

    protected string $path = '/wash_tones/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de tono de lavado: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
