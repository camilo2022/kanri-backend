<?php

namespace App\Mcp\Prompts\SupplyType;

use App\Mcp\Prompts\RoutePrompt;

class SupplyTypeDeletePrompt extends RoutePrompt
{
    protected string $name = 'supply_type_delete';

    protected string $title = 'Desactivar un tipo de insumo';

    protected string $description = 'Desactivar un tipo de insumo';

    protected string $method = 'DELETE';

    protected string $path = '/typification/supply_types/delete/{id}';

    protected string $business = 'Elimina (borrado lógico) un tipo de insumo. Se RECHAZA si no existe o ya está eliminado. En el código revisado no se verifica si existen insumos de ese tipo, así que no se puede asegurar que el sistema lo impida. Mientras esté eliminado, no se comprobó que su apartado de orden de producción quede libre para otro tipo. Antes de ejecutarlo, confirma con la persona el tipo de insumo exacto. Es reversible con la ruta de restauración, que conserva sus indicadores y su formulario.';
}
