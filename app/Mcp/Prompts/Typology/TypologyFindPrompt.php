<?php

namespace App\Mcp\Prompts\Typology;

use App\Mcp\Prompts\RoutePrompt;

class TypologyFindPrompt extends RoutePrompt
{
    protected string $name = 'typology_find';

    protected string $title = 'Obtener una tipología';

    protected string $description = 'Obtener una tipología';

    protected string $method = 'GET';

    protected string $path = '/typification/typologies/find/{id}';

    protected string $business = 'Trae un solo registro de tipología por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
