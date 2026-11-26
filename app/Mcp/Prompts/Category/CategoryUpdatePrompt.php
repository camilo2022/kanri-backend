<?php

namespace App\Mcp\Prompts\Category;

use App\Mcp\Prompts\RoutePrompt;

class CategoryUpdatePrompt extends RoutePrompt
{
    protected string $name = 'category_update';

    protected string $title = 'Editar una categoría';

    protected string $description = 'Editar una categoría';

    protected string $method = 'PUT';

    protected string $path = '/categorization/categories/update/{id}';

    protected string $business = 'Modifica un registro existente de category, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un category. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.';
}
