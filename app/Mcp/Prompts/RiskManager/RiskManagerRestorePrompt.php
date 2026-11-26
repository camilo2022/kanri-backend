<?php

namespace App\Mcp\Prompts\RiskManager;

use App\Mcp\Prompts\RoutePrompt;

class RiskManagerRestorePrompt extends RoutePrompt
{
    protected string $name = 'risk_manager_restore';

    protected string $title = 'Restaurar una administradora de riesgos desactivada';

    protected string $description = 'Restaurar una administradora de riesgos desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/risk_managers/restore/{id}';

    protected string $business = 'Reactiva un registro de risk manager que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un risk manager. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
