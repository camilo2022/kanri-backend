<?php

namespace App\Mcp\Prompts\BootType;

use App\Mcp\Prompts\RoutePrompt;

class BootTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'boot_type_find';

    protected string $title = 'Obtener un tipo de bota';

    protected string $description = 'Obtener un tipo de bota';

    protected string $method = 'GET';

    protected string $path = '/typification/boot_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de bota por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
