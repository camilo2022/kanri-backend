<?php

namespace App\Mcp\Prompts\Product;

use App\Mcp\Prompts\RoutePrompt;

class ProductFindPrompt extends RoutePrompt
{
    protected string $name = 'product_find';

    protected string $title = 'Consultar un producto';

    protected string $description = 'Consultar un producto';

    protected string $method = 'GET';

    protected string $path = '/products/find/{id}';

    protected string $business = 'Trae un producto (referencia) por su id numérico con su marca (con grupo y tallas) y su subcategoría (con categoría), más los catálogos de estados posibles de ficha y de sus detalles y los tipos de modelo que alimentan las tablas dinámicas. Úsala cuando la persona quiera los datos básicos de una referencia ya identificada. A diferencia de la lista de productos, no trae la ficha técnica ni las órdenes de producción: para la ficha se usa la consulta de fichas técnicas con el parámetro product_id. El id es numérico.';
}
