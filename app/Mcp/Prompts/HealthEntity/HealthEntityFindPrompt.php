<?php

namespace App\Mcp\Prompts\HealthEntity;

use App\Mcp\Prompts\RoutePrompt;

class HealthEntityFindPrompt extends RoutePrompt
{
    protected string $name = 'health_entity_find';

    protected string $title = 'Obtener una entidad de salud';

    protected string $description = 'Obtener una entidad de salud';

    protected string $method = 'GET';

    protected string $path = '/health_entities/find/{id}';

    protected string $business = 'Trae un solo registro de entidad de salud por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
