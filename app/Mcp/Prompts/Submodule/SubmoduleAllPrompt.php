<?php

namespace App\Mcp\Prompts\Submodule;

use App\Mcp\Prompts\RoutePrompt;

class SubmoduleAllPrompt extends RoutePrompt
{
    protected string $name = 'submodule_all';

    protected string $title = 'Listar los submódulos de un módulo';

    protected string $description = 'Listar los submódulos de un módulo';

    protected string $method = 'GET';

    protected string $path = '/navegation/modules/submodules/all/{module_id}';

    protected string $business = 'Lista los submódulos de un módulo del menú; el id del módulo va en la ruta. Cada submódulo es una pantalla del sistema y está ligado a un permiso, que es el que decide quién la ve. Úsala cuando pregunten qué pantallas tiene un módulo o qué permiso abre cada una. Trae nombre, dirección de la pantalla, icono, el módulo y el permiso. Admite búsqueda por texto y with_trashed para incluir los inactivos.';
}
