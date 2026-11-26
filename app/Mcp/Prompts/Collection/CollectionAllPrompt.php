<?php

namespace App\Mcp\Prompts\Collection;

use App\Mcp\Prompts\RoutePrompt;

class CollectionAllPrompt extends RoutePrompt
{
    protected string $name = 'collection_all';

    protected string $title = 'Listar las colecciones';

    protected string $description = 'Listar las colecciones';

    protected string $method = 'GET';

    protected string $path = '/collections/all';

    protected string $business = 'Devuelve la lista de colecciones registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar colecciones, o cuando necesites el id de uno para otra acción.';
}
