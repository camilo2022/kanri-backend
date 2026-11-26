<?php

namespace App\Mcp\Prompts\GarmentType;

use App\Mcp\Prompts\RoutePrompt;

class GarmentTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'garment_type_find';

    protected string $title = 'Obtener un tipo de prenda';

    protected string $description = 'Obtener un tipo de prenda';

    protected string $method = 'GET';

    protected string $path = '/typification/garment_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de prenda por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
