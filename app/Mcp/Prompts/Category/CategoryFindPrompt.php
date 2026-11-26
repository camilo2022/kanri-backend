<?php

namespace App\Mcp\Prompts\Category;

use App\Mcp\Prompts\RoutePrompt;

class CategoryFindPrompt extends RoutePrompt
{
    protected string $name = 'category_find';

    protected string $title = 'Obtener una categoría';

    protected string $description = 'Obtener una categoría';

    protected string $method = 'GET';

    protected string $path = '/categorization/categories/find/{id}';

    protected string $business = 'Trae un solo registro de categoría por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.';
}
