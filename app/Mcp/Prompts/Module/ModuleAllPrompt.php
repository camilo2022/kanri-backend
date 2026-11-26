<?php

namespace App\Mcp\Prompts\Module;

use App\Mcp\Prompts\RoutePrompt;

class ModuleAllPrompt extends RoutePrompt
{
    protected string $name = 'module_all';

    protected string $title = 'Listar los módulos del menú';

    protected string $description = 'Listar los módulos del menú';

    protected string $method = 'GET';

    protected string $path = '/navegation/modules/all';

    protected string $business = 'Lista los módulos del menú de Kanri, que agrupan las pantallas del sistema; cada módulo contiene sus submódulos. Úsala cuando pregunten cómo está organizado el menú o qué pantallas tiene un módulo. Cada módulo llega con su nombre, su icono y sus submódulos. La búsqueda mira el nombre y el icono. Admite with_trashed para incluir los módulos inactivos, que por defecto no aparecen.';
}
