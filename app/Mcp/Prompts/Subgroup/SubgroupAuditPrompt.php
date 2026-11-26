<?php

namespace App\Mcp\Prompts\Subgroup;

use App\Mcp\Prompts\RoutePrompt;

class SubgroupAuditPrompt extends RoutePrompt
{
    protected string $name = 'subgroup_audit';

    protected string $title = 'Historial de cambios de un subgrupo';

    protected string $description = 'Historial de cambios de un subgrupo';

    protected string $method = 'GET';

    protected string $path = '/classification/subgroups/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de subgrupo: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
