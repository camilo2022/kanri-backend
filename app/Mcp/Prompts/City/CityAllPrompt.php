<?php

namespace App\Mcp\Prompts\City;

use App\Mcp\Prompts\RoutePrompt;

class CityAllPrompt extends RoutePrompt
{
    protected string $name = 'city_all';

    protected string $title = 'Listar las ciudades de un departamento';

    protected string $description = 'Listar las ciudades de un departamento';

    protected string $method = 'GET';

    protected string $path = '/locations/cities/all/{department_id}';

    protected string $business = 'Lista las ciudades de un departamento; el id del departamento va en la ruta. Sirve para elegir la ubicación de una persona, que en Kanri se guarda en tablas propias de país, departamento y ciudad. Úsala cuando pregunten qué ciudades tiene un departamento o necesiten el id de una ciudad. Solo trae id, departamento y nombre. Busca por nombre y, si no se pide otro orden, ordena alfabéticamente. No hay borrado lógico.';
}
