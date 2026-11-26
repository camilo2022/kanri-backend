<?php

namespace App\Mcp\Prompts\SupplyType;

use App\Mcp\Prompts\RoutePrompt;

class SupplyTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'supply_type_find';

    protected string $title = 'Obtener un tipo de insumo';

    protected string $description = 'Obtener un tipo de insumo';

    protected string $method = 'GET';

    protected string $path = '/typification/supply_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de insumo por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
