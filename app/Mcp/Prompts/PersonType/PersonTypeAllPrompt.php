<?php

namespace App\Mcp\Prompts\PersonType;

use App\Mcp\Prompts\RoutePrompt;

class PersonTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'person_type_all';

    protected string $title = 'Listar los tipos de persona';

    protected string $description = 'Listar los tipos de persona';

    protected string $method = 'GET';

    protected string $path = '/typification/person_types/all';

    protected string $business = 'Devuelve la lista de tipos de persona registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar tipos de persona, o cuando necesites el id de uno para otra acción.

Notas: Cada tipo de persona trae sus tipos de documento. En esta lista no viene `item_id`.';
}
