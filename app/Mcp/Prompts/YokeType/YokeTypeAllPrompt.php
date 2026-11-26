<?php

namespace App\Mcp\Prompts\YokeType;

use App\Mcp\Prompts\RoutePrompt;

class YokeTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'yoke_type_all';

    protected string $title = 'Listar los tipos de cotilla';

    protected string $description = 'Listar los tipos de cotilla';

    protected string $method = 'GET';

    protected string $path = '/typification/yoke_types/all';

    protected string $business = 'Devuelve la lista de tipos de cotilla registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar tipos de cotilla, o cuando necesites el id de uno para otra acción.

Notas: También la pueden consultar quienes configuran procesos, subprocesos u operaciones.';
}
