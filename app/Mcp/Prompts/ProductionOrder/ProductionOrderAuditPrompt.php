<?php

namespace App\Mcp\Prompts\ProductionOrder;

use App\Mcp\Prompts\RoutePrompt;

class ProductionOrderAuditPrompt extends RoutePrompt
{
    protected string $name = 'production_order_audit';

    protected string $title = 'Historial de cambios de una orden de producción';

    protected string $description = 'Historial de cambios de una orden de producción';

    protected string $method = 'GET';

    protected string $path = '/technical_sheets/production_orders/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de órdenes de producción de una ficha técnica: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
