<?php

namespace App\Mcp\Prompts\PensionFund;

use App\Mcp\Prompts\RoutePrompt;

class PensionFundFindPrompt extends RoutePrompt
{
    protected string $name = 'pension_fund_find';

    protected string $title = 'Obtener un fondo de pensión';

    protected string $description = 'Obtener un fondo de pensión';

    protected string $method = 'GET';

    protected string $path = '/pension_funds/find/{id}';

    protected string $business = 'Trae un solo registro de fondo de pensión por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
