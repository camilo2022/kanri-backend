<?php

namespace App\Mcp\Prompts\Product;

use App\Mcp\Prompts\RoutePrompt;

class ProductAllPrompt extends RoutePrompt
{
    protected string $name = 'product_all';

    protected string $title = 'Listar los productos';

    protected string $description = 'Listar los productos';

    protected string $method = 'GET';

    protected string $path = '/products/all';

    protected string $business = 'Lista los productos, que son las referencias de la confección (por ejemplo BO1234), cada una con su marca (con grupo y tallas), su subcategoría (con categoría) y su ficha técnica. Úsala cuando la persona diga «busca la referencia BO001», «¿qué productos hay de la marca X?» o «¿en qué estado está la ficha de esta referencia?». La búsqueda separa el texto en palabras, ignora los guiones y busca cada palabra en el código, marca, grupo, subcategoría, categoría y datos de la ficha (código, consecutivo, estado, colección, tipo de prenda, tono de lavado y tipo de bota). Admite orden (incluso por el estado de la ficha en cada proceso) y paginación; sin per_page devuelve todo en una página. with_trashed es obligatorio e incluye los productos desactivados. Si el producto es STARA, technical_sheet es la primera ficha a la que está ligado y original es falso. No filtra por lote ni por orden de producción.';
}
