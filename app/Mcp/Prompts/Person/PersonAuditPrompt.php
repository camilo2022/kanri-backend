<?php

namespace App\Mcp\Prompts\Person;

use App\Mcp\Prompts\RoutePrompt;

class PersonAuditPrompt extends RoutePrompt
{
    protected string $name = 'person_audit';

    protected string $title = 'Historial de cambios de una persona';

    protected string $description = 'Historial de cambios de una persona';

    protected string $method = 'GET';

    protected string $path = '/people/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de persona: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
