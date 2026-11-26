<?php

namespace App\Mcp\Prompts\Gender;

use App\Mcp\Prompts\RoutePrompt;

class GenderFindPrompt extends RoutePrompt
{
    protected string $name = 'gender_find';

    protected string $title = 'Obtener un género';

    protected string $description = 'Obtener un género';

    protected string $method = 'GET';

    protected string $path = '/genders/find/{id}';

    protected string $business = 'Trae un solo registro de género por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
