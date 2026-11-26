<?php

namespace App\Mcp\Prompts\SupplyType;

use App\Mcp\Prompts\RoutePrompt;

class SupplyTypeRestorePrompt extends RoutePrompt
{
    protected string $name = 'supply_type_restore';

    protected string $title = 'Restaurar un tipo de insumo desactivado';

    protected string $description = 'Restaurar un tipo de insumo desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/supply_types/restore/{id}';

    protected string $business = 'Reactiva un tipo de insumo eliminado con sus indicadores y formulario originales. Se RECHAZA si no existe o no está eliminado. En el código revisado la restauración no vuelve a comprobar que el apartado de la orden de producción (fabric o roll) no esté ya ocupado por otro tipo de insumo; podría quedar duplicado. No hay conflicto de nombre. Confirma con la persona cuál tipo recuperar. Es reversible: se puede volver a eliminar.';
}
