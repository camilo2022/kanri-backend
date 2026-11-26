<?php

namespace App\Mcp\Prompts\PersonType;

use App\Mcp\Prompts\RoutePrompt;

class PersonTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'person_type_find';

    protected string $title = 'Obtener un tipo de persona';

    protected string $description = 'Obtener un tipo de persona';

    protected string $method = 'GET';

    protected string $path = '/typification/person_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de persona por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
