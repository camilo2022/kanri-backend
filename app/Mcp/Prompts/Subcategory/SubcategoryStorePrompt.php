<?php

namespace App\Mcp\Prompts\Subcategory;

use App\Mcp\Prompts\RoutePrompt;

class SubcategoryStorePrompt extends RoutePrompt
{
    protected string $name = 'subcategory_store';

    protected string $title = 'Crear una subcategoría';

    protected string $description = 'Crear una subcategoría';

    protected string $method = 'POST';

    protected string $path = '/categorization/categories/subcategories/store';

    protected string $business = 'Crea un registro nuevo de subcategory. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar subcategory. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.

Notas: Crea la subcategoría y la asocia a la categoría `category_id`.';
}
