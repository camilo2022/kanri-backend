<?php

namespace App\Mcp\Prompts\Category;

use App\Mcp\Prompts\RoutePrompt;

class CategoryAuditPrompt extends RoutePrompt
{
    protected string $name = 'category_audit';

    protected string $title = 'Historial de cambios de una categoría';

    protected string $description = 'Historial de cambios de una categoría';

    protected string $method = 'GET';

    protected string $path = '/categorization/categories/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de categoría: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
