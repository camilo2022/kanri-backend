<?php

namespace App\Mcp\Prompts\HealthEntity;

use App\Mcp\Prompts\RoutePrompt;

class HealthEntityAuditPrompt extends RoutePrompt
{
    protected string $name = 'health_entity_audit';

    protected string $title = 'Historial de cambios de una entidad de salud';

    protected string $description = 'Historial de cambios de una entidad de salud';

    protected string $method = 'GET';

    protected string $path = '/health_entities/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de entidad de salud: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
