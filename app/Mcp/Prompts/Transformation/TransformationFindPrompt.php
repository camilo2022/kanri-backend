<?php

namespace App\Mcp\Prompts\Transformation;

use App\Mcp\Prompts\RoutePrompt;

class TransformationFindPrompt extends RoutePrompt
{
    protected string $name = 'transformation_find';

    protected string $title = 'Obtener la ficha técnica de una transformación';

    protected string $description = 'Obtener la ficha técnica de una transformación';

    protected string $method = 'GET';

    protected string $path = '/technical_sheets/transformations/find/{id}';

    protected string $business = 'Consulta la ficha técnica de una transformación, que es una ficha creada a partir de otra ficha ya existente (queda ligada a la original). Devuelve el mismo formato que la consulta normal de ficha técnica. Ojo: el id de la ruta no se usa; la ficha se busca con exactamente UNO de estos parámetros de consulta: id, product_id o uuid; sin ninguno o con más de uno responde error de validación. Úsala cuando la persona pida la ficha de un producto que resultó de una transformación. A diferencia de la consulta normal de fichas, no carga las tipologías de los detalles. Incluye colección, tipo de prenda, tono, bota, patronista, insumos con su tipo y el producto con marca y tallas.';
}
