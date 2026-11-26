<?php

namespace App\Mcp\Prompts\BootType;

use App\Mcp\Prompts\RoutePrompt;

class BootTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'boot_type_all';

    protected string $title = 'Listar los tipos de bota';

    protected string $description = 'Listar los tipos de bota';

    protected string $method = 'GET';

    protected string $path = '/typification/boot_types/all';

    protected string $business = 'Devuelve la lista de tipos de bota registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar tipos de bota, o cuando necesites el id de uno para otra acción.';
}
