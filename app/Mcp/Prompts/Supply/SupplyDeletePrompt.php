<?php

namespace App\Mcp\Prompts\Supply;

use App\Mcp\Prompts\RoutePrompt;

class SupplyDeletePrompt extends RoutePrompt
{
    protected string $name = 'supply_delete';

    protected string $title = 'Desactivar un insumo';

    protected string $description = 'Desactivar un insumo';

    protected string $method = 'DELETE';

    protected string $path = '/typification/supply_types/supplies/delete/{id}';

    protected string $business = 'Desactiva un insumo (eliminación lógica: se conserva con fecha de eliminación). Se rechaza si no existe o ya está desactivado. En el código leído no hay verificación de fichas técnicas u otros registros que usen el insumo; no se pudo confirmar el impacto sobre ellos. Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar el insumo y pedir confirmación expresa. Es reversible con la restauración de insumos.';
}
