<?php

namespace App\Mcp\Prompts\Group;

use App\Mcp\Prompts\RoutePrompt;

class GroupRestorePrompt extends RoutePrompt
{
    protected string $name = 'group_restore';

    protected string $title = 'Restaurar un grupo desactivado';

    protected string $description = 'Restaurar un grupo desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/classification/groups/restore/{id}';

    protected string $business = 'Reactiva un registro de group que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un group. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
