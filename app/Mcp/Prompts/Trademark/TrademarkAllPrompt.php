<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkAllPrompt extends RoutePrompt
{
    protected string $name = 'trademark_all';

    protected string $title = 'Listar las marcas';

    protected string $description = 'Listar las marcas';

    protected string $method = 'GET';

    protected string $path = '/trademarks/all';

    protected string $business = 'Lista las marcas de la empresa. Cada marca pertenece a un grupo (el grupo cuelga de la marca) y tiene las tallas con que se trabaja. Úsala cuando pregunten qué marcas hay, a qué grupo pertenece una o qué tallas maneja. Cada marca llega con su logo, su portada, su grupo, sus tallas y su configuración. Admite búsqueda por texto y with_trashed para incluir las marcas inactivas, que por defecto no aparecen.';
}
