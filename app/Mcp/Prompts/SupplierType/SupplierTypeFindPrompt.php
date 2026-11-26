<?php

namespace App\Mcp\Prompts\SupplierType;

use App\Mcp\Prompts\RoutePrompt;

class SupplierTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'supplier_type_find';

    protected string $title = 'Obtener un tipo de proveedor';

    protected string $description = 'Obtener un tipo de proveedor';

    protected string $method = 'GET';

    protected string $path = '/typification/supplier_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de proveedor por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
