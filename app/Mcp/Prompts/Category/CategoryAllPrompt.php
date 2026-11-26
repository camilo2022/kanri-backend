<?php

namespace App\Mcp\Prompts\Category;

use App\Mcp\Prompts\RoutePrompt;

class CategoryAllPrompt extends RoutePrompt
{
    protected string $name = 'category_all';

    protected string $title = 'Listar las categorías';

    protected string $description = 'Listar las categorías';

    protected string $method = 'GET';

    protected string $path = '/categorization/categories/all';

    protected string $business = 'Lista las categorías del producto, el primer nivel de la clasificación de las prendas; cada categoría tiene sus subcategorías colgando de ella. Úsala cuando pregunten qué categorías hay o qué subcategorías tiene una. Cada categoría llega con sus subcategorías, pero no con su configuración (settings). Admite buscar por texto, filtrar con subcategory_id para hallar la categoría que contiene esa subcategoría, y with_trashed para incluir las categorías inactivas (eliminadas lógicamente), que por defecto no aparecen.';
}
