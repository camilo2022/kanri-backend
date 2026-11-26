<?php

namespace App\Mcp\Prompts\SupplyType;

use App\Mcp\Prompts\RoutePrompt;

class SupplyTypeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'supply_type_update';

    protected string $title = 'Editar un tipo de insumo';

    protected string $description = 'Editar un tipo de insumo';

    protected string $method = 'PUT';

    protected string $path = '/typification/supply_types/update/{id}';

    protected string $business = 'Edita nombre, descripción e indicadores de un tipo de insumo: has_supplies, in_technical_sheet, in_production_order y paragraph (fabric o roll; obligatorio si aplica a la orden de producción). Se conserva el formulario de campos (settings.form) ya definido; el resto de settings se reemplaza por lo enviado. Se RECHAZA si el tipo no existe, si nombre o descripción no están en MAYÚSCULAS (uppercase rechaza), si el nombre tiene menos de 4 caracteres o lo usa otro tipo, o si otro tipo de insumo ya ocupa ese apartado de la orden de producción. Cambiar los indicadores afecta qué insumos aparecen en fichas técnicas y órdenes. Confirma con la persona los cambios. Es reversible.';
}
