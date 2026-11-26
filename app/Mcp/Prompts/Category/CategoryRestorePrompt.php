<?php

namespace App\Mcp\Prompts\Category;

use App\Mcp\Prompts\RoutePrompt;

class CategoryRestorePrompt extends RoutePrompt
{
    protected string $name = 'category_restore';

    protected string $title = 'Restaurar una categoría desactivada';

    protected string $description = 'Restaurar una categoría desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/categorization/categories/restore/{id}';

    protected string $business = 'Reactiva un registro de category que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un category. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
