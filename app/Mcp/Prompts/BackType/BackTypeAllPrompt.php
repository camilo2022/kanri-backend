<?php

namespace App\Mcp\Prompts\BackType;

use App\Mcp\Prompts\RoutePrompt;

class BackTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'back_type_all';

    protected string $title = 'Listar los tipos de trasero';

    protected string $description = 'Listar los tipos de trasero';

    protected string $method = 'GET';

    protected string $path = '/typification/back_types/all';

    protected string $business = 'Devuelve la lista de tipos de trasero registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar tipos de trasero, o cuando necesites el id de uno para otra acción.';
}
