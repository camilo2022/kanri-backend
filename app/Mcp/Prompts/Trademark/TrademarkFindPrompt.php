<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkFindPrompt extends RoutePrompt
{
    protected string $name = 'trademark_find';

    protected string $title = 'Obtener una marca';

    protected string $description = 'Obtener una marca';

    protected string $method = 'GET';

    protected string $path = '/trademarks/find/{id}';

    protected string $business = 'Muestra una marca concreta con su logo, su portada, su grupo, sus tallas y su configuración. Úsala cuando pregunten por una marca puntual y ya se conoce su id. Es solo consulta; si no existe responde que no se encontró.';
}
