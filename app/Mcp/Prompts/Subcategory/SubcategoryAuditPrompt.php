<?php

namespace App\Mcp\Prompts\Subcategory;

use App\Mcp\Prompts\RoutePrompt;

class SubcategoryAuditPrompt extends RoutePrompt
{
    protected string $name = 'subcategory_audit';

    protected string $title = 'Historial de cambios de una subcategoría';

    protected string $description = 'Historial de cambios de una subcategoría';

    protected string $method = 'GET';

    protected string $path = '/categorization/categories/subcategories/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de subcategoría: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
