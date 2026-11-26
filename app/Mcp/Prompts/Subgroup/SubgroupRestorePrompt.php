<?php

namespace App\Mcp\Prompts\Subgroup;

use App\Mcp\Prompts\RoutePrompt;

class SubgroupRestorePrompt extends RoutePrompt
{
    protected string $name = 'subgroup_restore';

    protected string $title = 'Restaurar un subgrupo desactivado';

    protected string $description = 'Restaurar un subgrupo desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/classification/subgroups/restore/{id}';

    protected string $business = 'Reactiva un registro de subgroup que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un subgroup. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
