<?php

namespace App\Mcp\Prompts\PensionFund;

use App\Mcp\Prompts\RoutePrompt;

class PensionFundDeletePrompt extends RoutePrompt
{
    protected string $name = 'pension_fund_delete';

    protected string $title = 'Desactivar un fondo de pensión';

    protected string $description = 'Desactivar un fondo de pensión';

    protected string $method = 'DELETE';

    protected string $path = '/pension_funds/delete/{id}';

    protected string $business = 'Elimina un registro de pension fund, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un pension fund. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
