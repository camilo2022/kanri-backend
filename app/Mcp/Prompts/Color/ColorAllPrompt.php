<?php

namespace App\Mcp\Prompts\Color;

use App\Mcp\Prompts\RoutePrompt;

class ColorAllPrompt extends RoutePrompt
{
    protected string $name = 'color_all';

    protected string $title = 'Listar los colores';

    protected string $description = 'Listar los colores';

    protected string $method = 'GET';

    protected string $path = '/colors/all';

    protected string $business = 'Lista los colores del catálogo, que se usan en el producto y en las fichas técnicas. Úsala cuando pregunten qué colores hay o cuáles se usan en producto terminado. Trae nombre y descripción de cada color. Admite búsqueda por texto, with_trashed para incluir los inactivos (eliminados lógicamente) y el filtro is_finished_product, que se apoya en la marca de «aplica a producto terminado» guardada en la configuración del color: verdadero deja solo los de producto terminado y falso los que no lo son.';
}
