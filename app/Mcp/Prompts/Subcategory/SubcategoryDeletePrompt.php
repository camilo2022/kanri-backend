<?php

namespace App\Mcp\Prompts\Subcategory;

use App\Mcp\Prompts\RoutePrompt;

class SubcategoryDeletePrompt extends RoutePrompt
{
    protected string $name = 'subcategory_delete';

    protected string $title = 'Desactivar una subcategoría';

    protected string $description = 'Desactivar una subcategoría';

    protected string $method = 'DELETE';

    protected string $path = '/categorization/categories/subcategories/delete/{id}';

    protected string $business = 'Elimina un registro de subcategory, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un subcategory. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
