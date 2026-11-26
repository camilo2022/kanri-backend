<?php

namespace App\Mcp\Prompts\GarmentType;

use App\Mcp\Prompts\RoutePrompt;

class GarmentTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'garment_type_all';

    protected string $title = 'Listar los tipos de prenda';

    protected string $description = 'Listar los tipos de prenda';

    protected string $method = 'GET';

    protected string $path = '/typification/garment_types/all';

    protected string $business = 'Devuelve la lista de tipos de prenda registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar tipos de prenda, o cuando necesites el id de uno para otra acción.';
}
