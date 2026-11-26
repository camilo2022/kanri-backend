<?php

namespace App\Mcp\Prompts\Product;

use App\Mcp\Prompts\RoutePrompt;

class ProductAuditPrompt extends RoutePrompt
{
    protected string $name = 'product_audit';

    protected string $title = 'Historial de cambios de un producto';

    protected string $description = 'Historial de cambios de un producto';

    protected string $method = 'GET';

    protected string $path = '/products/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de productos: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.

Notas: Cada grupo de rutas con listado tiene su `/audit/{id}` (por ejemplo `/typification/supply_types/supplies/audit/{id}`): todas devuelven solo los cambios de ese registro, del más reciente al más antiguo, con nombres legibles. Los eventos sin datos no se incluyen, y los +1 y −1 seguidos de un registro de trabajo llegan como un solo evento `units`.';
}
