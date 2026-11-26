<?php

namespace App\Mcp\Prompts\Bank;

use App\Mcp\Prompts\RoutePrompt;

class BankFindPrompt extends RoutePrompt
{
    protected string $name = 'bank_find';

    protected string $title = 'Obtener un banco';

    protected string $description = 'Obtener un banco';

    protected string $method = 'GET';

    protected string $path = '/banks/find/{id}';

    protected string $business = 'Trae un solo registro de banco por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
