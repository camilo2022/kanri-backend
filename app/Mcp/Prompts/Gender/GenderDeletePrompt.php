<?php

namespace App\Mcp\Prompts\Gender;

use App\Mcp\Prompts\RoutePrompt;

class GenderDeletePrompt extends RoutePrompt
{
    protected string $name = 'gender_delete';

    protected string $title = 'Desactivar un género';

    protected string $description = 'Desactivar un género';

    protected string $method = 'DELETE';

    protected string $path = '/genders/delete/{id}';

    protected string $business = 'Elimina un registro de gender, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un gender. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
