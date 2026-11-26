<?php

namespace App\Mcp\Prompts\AccountType;

use App\Mcp\Prompts\RoutePrompt;

class AccountTypeRestorePrompt extends RoutePrompt
{
    protected string $name = 'account_type_restore';

    protected string $title = 'Restaurar un tipo de cuenta desactivado';

    protected string $description = 'Restaurar un tipo de cuenta desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/account_types/restore/{id}';

    protected string $business = 'Reactiva un registro de account type que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un account type. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
