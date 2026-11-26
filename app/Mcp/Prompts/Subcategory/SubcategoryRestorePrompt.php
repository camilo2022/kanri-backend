<?php

namespace App\Mcp\Prompts\Subcategory;

use App\Mcp\Prompts\RoutePrompt;

class SubcategoryRestorePrompt extends RoutePrompt
{
    protected string $name = 'subcategory_restore';

    protected string $title = 'Restaurar una subcategoría desactivada';

    protected string $description = 'Restaurar una subcategoría desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/categorization/categories/subcategories/restore/{id}';

    protected string $business = 'Reactiva un registro de subcategory que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un subcategory. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
