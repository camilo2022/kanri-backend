<?php

namespace App\Mcp\Prompts\Supply;

use App\Mcp\Prompts\RoutePrompt;

class SupplyExportPrompt extends RoutePrompt
{
    protected string $name = 'supply_export';

    protected string $title = 'Exportar a Excel los insumos de un tipo';

    protected string $description = 'Exportar a Excel los insumos de un tipo';

    protected string $method = 'GET';

    protected string $path = '/typification/supply_types/supplies/export/{supply_type_id}';

    protected string $business = 'Descarga `insumos.xlsx`: una hoja con los insumos del tipo y, por cada campo `selectdinamic` múltiple del formulario, una hoja con sus relaciones. Sirve también como plantilla para importar.';
}
