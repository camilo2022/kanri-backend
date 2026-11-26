<?php

namespace App\Mcp\Prompts\PensionFund;

use App\Mcp\Prompts\RoutePrompt;

class PensionFundRestorePrompt extends RoutePrompt
{
    protected string $name = 'pension_fund_restore';

    protected string $title = 'Restaurar un fondo de pensión desactivado';

    protected string $description = 'Restaurar un fondo de pensión desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/pension_funds/restore/{id}';

    protected string $business = 'Reactiva un registro de pension fund que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un pension fund. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
