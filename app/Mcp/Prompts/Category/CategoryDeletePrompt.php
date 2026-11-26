<?php

namespace App\Mcp\Prompts\Category;

use App\Mcp\Prompts\RoutePrompt;

class CategoryDeletePrompt extends RoutePrompt
{
    protected string $name = 'category_delete';

    protected string $title = 'Desactivar una categoría';

    protected string $description = 'Desactivar una categoría';

    protected string $method = 'DELETE';

    protected string $path = '/categorization/categories/delete/{id}';

    protected string $business = 'Elimina un registro de category, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un category. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
