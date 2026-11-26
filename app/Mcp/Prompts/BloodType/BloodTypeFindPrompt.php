<?php

namespace App\Mcp\Prompts\BloodType;

use App\Mcp\Prompts\RoutePrompt;

class BloodTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'blood_type_find';

    protected string $title = 'Obtener un tipo de sangre';

    protected string $description = 'Obtener un tipo de sangre';

    protected string $method = 'GET';

    protected string $path = '/typification/blood_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de sangre por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
