<?php

namespace App\Mcp\Prompts\Subcategory;

use App\Mcp\Prompts\RoutePrompt;

class SubcategoryUpdatePrompt extends RoutePrompt
{
    protected string $name = 'subcategory_update';

    protected string $title = 'Editar una subcategoría';

    protected string $description = 'Editar una subcategoría';

    protected string $method = 'PUT';

    protected string $path = '/categorization/categories/subcategories/update/{id}';

    protected string $business = 'Modifica un registro existente de subcategory, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un subcategory. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre y descripción; no cambia las categorías.';
}
