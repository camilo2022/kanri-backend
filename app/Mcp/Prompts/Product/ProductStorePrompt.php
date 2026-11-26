<?php

namespace App\Mcp\Prompts\Product;

use App\Mcp\Prompts\RoutePrompt;

class ProductStorePrompt extends RoutePrompt
{
    protected string $name = 'product_store';

    protected string $title = 'Crear un producto';

    protected string $description = 'Crear un producto';

    protected string $method = 'POST';

    protected string $path = '/products/store';

    protected string $business = 'Crea un producto (referencia) con código, marca y subcategoría. El código debe ser único y cumplir al menos una de las validaciones (expresiones regulares) configuradas en la marca; si la marca no tiene validaciones, siempre falla. Con reference_relation=true se crea como producto STARA: el sistema fuerza la marca «STARA DENIM» (ignora la marca enviada) y lo asocia a la ficha técnica indicada en technical_sheet_id (relación auditada). No se pudo confirmar qué pasa si reference_relation es verdadero y no se envía technical_sheet_id (la relación se intentaría con un valor vacío). No crea la ficha técnica ni la orden; solo el producto. Al crear la ficha técnica, el producto no puede estar ya ligado a otra (product_id único). Confirmar con la persona el código exacto, la marca, la subcategoría y si es referencia STARA de una ficha. No hay ruta de eliminación en este alcance, por lo que corregir un error exige editarlo.';
}
