<?php

namespace App\Mcp\Prompts\Supply;

use App\Mcp\Prompts\RoutePrompt;

class SupplyImportPrompt extends RoutePrompt
{
    protected string $name = 'supply_import';

    protected string $title = 'Importar insumos desde Excel';

    protected string $description = 'Importar insumos desde Excel';

    protected string $method = 'POST';

    protected string $path = '/typification/supply_types/supplies/import/{supply_type_id}';

    protected string $business = 'Lee el archivo (mismo formato que la exportación), valida todas las filas y, si no hay errores, crea o actualiza cada insumo por su nombre. Si hay errores, responde 422 con un reporte en texto (`file`, ruta en el disco `public`). El archivo debe ser un Excel (.xlsx) y el `{supply_type_id}` inexistente responde 404.';
}
