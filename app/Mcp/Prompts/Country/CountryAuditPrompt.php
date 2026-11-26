<?php

namespace App\Mcp\Prompts\Country;

use App\Mcp\Prompts\RoutePrompt;

class CountryAuditPrompt extends RoutePrompt
{
    protected string $name = 'country_audit';

    protected string $title = 'Historial de cambios de un país';

    protected string $description = 'Historial de cambios de un país';

    protected string $method = 'GET';

    protected string $path = '/locations/countries/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de países: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
