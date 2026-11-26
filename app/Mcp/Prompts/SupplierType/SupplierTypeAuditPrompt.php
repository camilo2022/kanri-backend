<?php

namespace App\Mcp\Prompts\SupplierType;

use App\Mcp\Prompts\RoutePrompt;

class SupplierTypeAuditPrompt extends RoutePrompt
{
    protected string $name = 'supplier_type_audit';

    protected string $title = 'Historial de cambios de un tipo de proveedor';

    protected string $description = 'Historial de cambios de un tipo de proveedor';

    protected string $method = 'GET';

    protected string $path = '/typification/supplier_types/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de tipo de proveedor: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
