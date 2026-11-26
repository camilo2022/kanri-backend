<?php

namespace App\Mcp\Prompts\AccountType;

use App\Mcp\Prompts\RoutePrompt;

class AccountTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'account_type_find';

    protected string $title = 'Obtener un tipo de cuenta';

    protected string $description = 'Obtener un tipo de cuenta';

    protected string $method = 'GET';

    protected string $path = '/typification/account_types/find/{id}';

    protected string $business = 'Trae un solo registro de tipo de cuenta por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
