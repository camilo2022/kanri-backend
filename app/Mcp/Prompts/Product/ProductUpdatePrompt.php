<?php

namespace App\Mcp\Prompts\Product;

use App\Mcp\Prompts\RoutePrompt;

class ProductUpdatePrompt extends RoutePrompt
{
    protected string $name = 'product_update';

    protected string $title = 'Editar un producto';

    protected string $description = 'Editar un producto';

    protected string $method = 'PUT';

    protected string $path = '/products/update/{id}';

    protected string $business = 'Edita el código, la marca y la subcategoría de un producto existente. El código debe ser único (se ignora el propio registro). Hallazgo importante: a diferencia de crear, no valida el formato del código contra las reglas de la marca, y tampoco revisa si el producto ya tiene ficha técnica u órdenes de producción, así que el cambio se propaga a todo lo que lo use. Responde con el producto en un objeto simple, no con el formato de recurso usado al crear. No modifica fichas ni órdenes, solo el registro del producto. Antes de ejecutarlo, confirmar con la persona el producto, los valores anteriores y nuevos, y avisar que el código aparece en fichas, órdenes y nombres de fotos (las fotos de ficha deben llamarse con el código del producto, por lo que cambiarlo puede obligar a renombrarlas). Es reversible volviendo a editar con los valores anteriores.';
}
