<?php

namespace App\Mcp\Prompts\Supply;

use App\Mcp\Prompts\RoutePrompt;

class SupplyFindPrompt extends RoutePrompt
{
    protected string $name = 'supply_find';

    protected string $title = 'Obtener un insumo';

    protected string $description = 'Obtener un insumo';

    protected string $method = 'GET';

    protected string $path = '/typification/supply_types/supplies/find/{id}';

    protected string $business = 'Trae un insumo (una tela, un hilo, un botón…) por su id numérico, con su tipo de insumo, sus archivos y sus relaciones dinámicas, que cada tipo define y que salen por su nombre y su campo (por ejemplo color y color_id). Úsala cuando la persona pida los datos de un insumo ya identificado, por ejemplo «muéstrame la tela X». No trae existencias ni las órdenes de producción donde se usa; para eso está la lista de insumos de un tipo.';
}
