<?php

namespace App\Mcp\Prompts\Subgroup;

use App\Mcp\Prompts\RoutePrompt;

class SubgroupDeletePrompt extends RoutePrompt
{
    protected string $name = 'subgroup_delete';

    protected string $title = 'Desactivar un subgrupo';

    protected string $description = 'Desactivar un subgrupo';

    protected string $method = 'DELETE';

    protected string $path = '/classification/subgroups/delete/{id}';

    protected string $business = 'Elimina un registro de subgroup, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un subgroup. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
