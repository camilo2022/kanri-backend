<?php

namespace App\Mcp\Prompts\Subcategory;

use App\Mcp\Prompts\RoutePrompt;

class SubcategoryFindPrompt extends RoutePrompt
{
    protected string $name = 'subcategory_find';

    protected string $title = 'Obtener una subcategoría';

    protected string $description = 'Obtener una subcategoría';

    protected string $method = 'GET';

    protected string $path = '/categorization/categories/subcategories/find/{id}';

    protected string $business = 'Trae un solo registro de subcategoría por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
