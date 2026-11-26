<?php

namespace App\Mcp\Prompts\Gender;

use App\Mcp\Prompts\RoutePrompt;

class GenderAllPrompt extends RoutePrompt
{
    protected string $name = 'gender_all';

    protected string $title = 'Listar los géneros';

    protected string $description = 'Listar los géneros';

    protected string $method = 'GET';

    protected string $path = '/genders/all';

    protected string $business = 'Devuelve la lista de géneros registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar géneros, o cuando necesites el id de uno para otra acción.

Notas: En esta lista no viene `settings`.';
}
