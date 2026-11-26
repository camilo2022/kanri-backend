<?php

namespace App\Mcp\Prompts\WaistbandType;

use App\Mcp\Prompts\RoutePrompt;

class WaistbandTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'waistband_type_find';

    protected string $title = 'Obtener un tipo de pretina';

    protected string $description = 'Obtener un tipo de pretina';

    protected string $method = 'GET';

    protected string $path = '/typification/waistband_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de pretina por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
