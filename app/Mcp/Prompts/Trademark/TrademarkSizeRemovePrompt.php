<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkSizeRemovePrompt extends RoutePrompt
{
    protected string $name = 'trademark_size_remove';

    protected string $title = 'Quitar una talla de una marca';

    protected string $description = 'Quitar una talla de una marca';

    protected string $method = 'POST';

    protected string $path = '/trademarks/size/remove/{id}';

    protected string $business = 'Quita una talla de una marca, es decir, deja de habilitarla en ella. Se rechaza si la marca o la talla no existen o si la talla no está asignada a esa marca. Efecto: se elimina solo esa relación y queda en la auditoría; la talla no se borra del catálogo. En el código leído no hay comprobación de si hay referencias, fichas u órdenes que usen esa talla en esa marca, así que no se pudo confirmar el impacto. Antes de ejecutarlo, la IA debe confirmar la marca y la talla y advertir ese riesgo. Es reversible volviendo a asignar la talla.';
}
