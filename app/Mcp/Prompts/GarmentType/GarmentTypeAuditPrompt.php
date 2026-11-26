<?php

namespace App\Mcp\Prompts\GarmentType;

use App\Mcp\Prompts\RoutePrompt;

class GarmentTypeAuditPrompt extends RoutePrompt
{
    protected string $name = 'garment_type_audit';

    protected string $title = 'Historial de cambios de un tipo de prenda';

    protected string $description = 'Historial de cambios de un tipo de prenda';

    protected string $method = 'GET';

    protected string $path = '/typification/garment_types/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de tipo de prenda: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
