<?php

namespace App\Mcp\Prompts\Module;

use App\Mcp\Prompts\RoutePrompt;

class ModuleFindPrompt extends RoutePrompt
{
    protected string $name = 'module_find';

    protected string $title = 'Obtener un módulo';

    protected string $description = 'Obtener un módulo';

    protected string $method = 'GET';

    protected string $path = '/navegation/modules/find/{id}';

    protected string $business = 'Muestra un módulo del menú con sus submódulos. Úsala cuando la persona quiera ver las pantallas de un módulo puntual y ya se conoce su id. Trae nombre, icono y la lista de submódulos. Es solo consulta; si no existe responde que no se encontró.';
}
