<?php

namespace App\Mcp\Prompts\CompensationFund;

use App\Mcp\Prompts\RoutePrompt;

class CompensationFundRestorePrompt extends RoutePrompt
{
    protected string $name = 'compensation_fund_restore';

    protected string $title = 'Restaurar una caja de compensación desactivada';

    protected string $description = 'Restaurar una caja de compensación desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/compensation_funds/restore/{id}';

    protected string $business = 'Reactiva un registro de compensation fund que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un compensation fund. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
