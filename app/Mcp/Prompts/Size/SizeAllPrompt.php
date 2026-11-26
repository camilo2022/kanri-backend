<?php

namespace App\Mcp\Prompts\Size;

use App\Mcp\Prompts\RoutePrompt;

class SizeAllPrompt extends RoutePrompt
{
    protected string $name = 'size_all';

    protected string $title = 'Listar las tallas';

    protected string $description = 'Listar las tallas';

    protected string $method = 'GET';

    protected string $path = '/sizes/all';

    protected string $business = 'Lista las tallas del catálogo, que se usan en las marcas, en las curvas de producción y en los movimientos. Úsala cuando pregunten qué tallas existen o cuáles se usan en producto terminado. Trae nombre, descripción y configuración de cada talla. Admite búsqueda por texto, with_trashed para incluir las inactivas (eliminadas lógicamente) y el filtro is_finished_product, que se apoya en la marca de «aplica a producto terminado» guardada en la configuración de la talla.';
}
