<?php

namespace App\Mcp\Prompts\SupplyType;

use App\Mcp\Prompts\RoutePrompt;

class SupplyTypeStorePrompt extends RoutePrompt
{
    protected string $name = 'supply_type_store';

    protected string $title = 'Crear un tipo de insumo';

    protected string $description = 'Crear un tipo de insumo';

    protected string $method = 'POST';

    protected string $path = '/typification/supply_types/store';

    protected string $business = 'Crea un tipo de insumo (por ejemplo tela, hilo) y define dónde aplica. Se usa para clasificar los insumos. Los indicadores de settings: has_supplies (si maneja insumos), in_technical_sheet (aplica a la ficha técnica), in_production_order (aplica a la orden de producción) y paragraph (apartado de la orden: fabric o roll), obligatorio si aplica a la orden de producción. Se RECHAZA si nombre o descripción no están en MAYÚSCULAS (uppercase rechaza), si el nombre tiene menos de 4 caracteres o ya existe, o si otro tipo de insumo ya usa ese mismo apartado en la orden (solo uno por apartado). El formulario de campos se define luego en la configuración. Confirma con la persona los indicadores. Es reversible.';
}
