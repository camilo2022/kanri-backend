<?php

namespace App\Mcp\Prompts\Gender;

use App\Mcp\Prompts\RoutePrompt;

class GenderRestorePrompt extends RoutePrompt
{
    protected string $name = 'gender_restore';

    protected string $title = 'Restaurar un género desactivado';

    protected string $description = 'Restaurar un género desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/genders/restore/{id}';

    protected string $business = 'Reactiva un registro de gender que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un gender. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
