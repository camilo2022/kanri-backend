<?php

namespace App\Mcp\Prompts\RiskManager;

use App\Mcp\Prompts\RoutePrompt;

class RiskManagerDeletePrompt extends RoutePrompt
{
    protected string $name = 'risk_manager_delete';

    protected string $title = 'Desactivar una administradora de riesgos';

    protected string $description = 'Desactivar una administradora de riesgos';

    protected string $method = 'DELETE';

    protected string $path = '/risk_managers/delete/{id}';

    protected string $business = 'Elimina un registro de risk manager, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un risk manager. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
