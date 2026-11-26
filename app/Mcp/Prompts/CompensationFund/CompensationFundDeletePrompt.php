<?php

namespace App\Mcp\Prompts\CompensationFund;

use App\Mcp\Prompts\RoutePrompt;

class CompensationFundDeletePrompt extends RoutePrompt
{
    protected string $name = 'compensation_fund_delete';

    protected string $title = 'Desactivar una caja de compensación';

    protected string $description = 'Desactivar una caja de compensación';

    protected string $method = 'DELETE';

    protected string $path = '/compensation_funds/delete/{id}';

    protected string $business = 'Elimina un registro de compensation fund, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un compensation fund. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
