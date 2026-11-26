<?php

namespace App\Mcp\Prompts\Supply;

use App\Mcp\Prompts\RoutePrompt;

class SupplyRestorePrompt extends RoutePrompt
{
    protected string $name = 'supply_restore';

    protected string $title = 'Restaurar un insumo desactivado';

    protected string $description = 'Restaurar un insumo desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/supply_types/supplies/restore/{id}';

    protected string $business = 'Reactiva un insumo desactivado con todos sus datos. Se rechaza si el insumo no existe o no está desactivado. No revalida sus valores contra el formulario actual del tipo. Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar el insumo y pedir confirmación. Es reversible: se puede volver a desactivar.';
}
