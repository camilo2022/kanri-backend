<?php

namespace App\Mcp\Prompts\Country;

use App\Mcp\Prompts\RoutePrompt;

class CountryAllPrompt extends RoutePrompt
{
    protected string $name = 'country_all';

    protected string $title = 'Listar los países';

    protected string $description = 'Listar los países';

    protected string $method = 'GET';

    protected string $path = '/locations/countries/all';

    protected string $business = 'Lista los países disponibles para ubicar a las personas y para el indicativo telefónico. Úsala cuando pregunten por un país, su código o su indicativo. Cada país trae nombre, códigos ISO2 e ISO3, indicativo telefónico y su configuración. La búsqueda acepta parte del nombre, o el código ISO2, ISO3 o el indicativo exactos. Ordena por nombre si no se indica otro orden. No hay borrado lógico.';
}
