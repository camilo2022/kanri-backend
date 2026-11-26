<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkDeletePrompt extends RoutePrompt
{
    protected string $name = 'trademark_delete';

    protected string $title = 'Desactivar una marca';

    protected string $description = 'Desactivar una marca';

    protected string $method = 'DELETE';

    protected string $path = '/trademarks/delete/{id}';

    protected string $business = 'Desactiva una marca (eliminación lógica: se conserva con fecha de eliminación, junto con sus tallas y su grupo). Se rechaza si no existe o ya está desactivada. En el código leído no hay verificación de referencias, fichas u órdenes que usen la marca; no se pudo confirmar el efecto sobre ellas. Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar la marca y pedir confirmación expresa. Es reversible con la restauración de marcas.';
}
