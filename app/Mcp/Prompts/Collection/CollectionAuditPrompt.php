<?php

namespace App\Mcp\Prompts\Collection;

use App\Mcp\Prompts\RoutePrompt;

class CollectionAuditPrompt extends RoutePrompt
{
    protected string $name = 'collection_audit';

    protected string $title = 'Historial de cambios de una colección';

    protected string $description = 'Historial de cambios de una colección';

    protected string $method = 'GET';

    protected string $path = '/collections/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de colección con sus fichas técnicas: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
