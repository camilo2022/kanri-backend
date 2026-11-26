<?php

namespace App\Mcp\Prompts\Submodule;

use App\Mcp\Prompts\RoutePrompt;

class SubmoduleFindPrompt extends RoutePrompt
{
    protected string $name = 'submodule_find';

    protected string $title = 'Obtener un submódulo';

    protected string $description = 'Obtener un submódulo';

    protected string $method = 'GET';

    protected string $path = '/navegation/modules/submodules/find/{id}';

    protected string $business = 'Muestra un submódulo (una pantalla del menú) con su módulo y el permiso que la controla. Úsala cuando la persona quiera saber a qué módulo pertenece una pantalla o qué permiso la habilita, y ya se conoce su id. Es solo consulta; si no existe responde que no se encontró.';
}
