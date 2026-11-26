<?php

namespace App\Mcp\Prompts\RiskManager;

use App\Mcp\Prompts\RoutePrompt;

class RiskManagerFindPrompt extends RoutePrompt
{
    protected string $name = 'risk_manager_find';

    protected string $title = 'Obtener una administradora de riesgos';

    protected string $description = 'Obtener una administradora de riesgos';

    protected string $method = 'GET';

    protected string $path = '/risk_managers/find/{id}';

    protected string $business = 'Trae un solo registro de administradora de riesgos por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
