<?php

namespace App\Mcp\Prompts\Subcategory;

use App\Mcp\Prompts\RoutePrompt;

class SubcategoryAllPrompt extends RoutePrompt
{
    protected string $name = 'subcategory_all';

    protected string $title = 'Listar las subcategorías de una categoría';

    protected string $description = 'Listar las subcategorías de una categoría';

    protected string $method = 'GET';

    protected string $path = '/categorization/categories/subcategories/all/{category_id}';

    protected string $business = 'Lista las subcategorías que pertenecen a una categoría concreta; el id de la categoría va en la ruta. Úsala cuando pregunten qué subcategorías tiene una categoría, por ejemplo las de un mundo de producto. Cada subcategoría llega con su categoría, pero no con su configuración (settings). Admite búsqueda por texto y with_trashed para incluir las inactivas, que por defecto no se listan.';
}
