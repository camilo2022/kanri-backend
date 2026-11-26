<?php

namespace App\Mcp\Prompts\Collection;

use App\Mcp\Prompts\RoutePrompt;

class CollectionFindPrompt extends RoutePrompt
{
    protected string $name = 'collection_find';

    protected string $title = 'Obtener una colección con sus fichas técnicas';

    protected string $description = 'Obtener una colección con sus fichas técnicas';

    protected string $method = 'GET';

    protected string $path = '/collections/find/{id}';

    protected string $business = 'Trae una colección (por ejemplo una temporada o campaña) por su id numérico, con los datos propios de la colección: nombre, descripción, configuración y el catálogo al que pertenece. Úsala cuando la persona pida los datos de una colección ya identificada, por ejemplo «muéstrame la colección Verano». Ojo: esta ruta no trae las fichas técnicas ni los lotes de la colección; para ver las fichas de una colección se usa la consulta de gestión de colecciones, y para programar sus lotes la de gestión de producción. El id debe ser el de una colección existente; un registro desactivado no se encuentra por esta ruta.';
}
