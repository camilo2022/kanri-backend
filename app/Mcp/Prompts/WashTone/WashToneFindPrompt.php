<?php

namespace App\Mcp\Prompts\WashTone;

use App\Mcp\Prompts\RoutePrompt;

class WashToneFindPrompt extends RoutePrompt
{
    protected string $name = 'wash_tone_find';

    protected string $title = 'Obtener un tono de lavado';

    protected string $description = 'Obtener un tono de lavado';

    protected string $method = 'GET';

    protected string $path = '/wash_tones/find/{id}';

    protected string $business = 'Trae un solo registro de tono de lavado por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
