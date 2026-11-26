<?php

namespace App\Mcp\Prompts\ProductionOrder;

use App\Mcp\Prompts\RoutePrompt;

class ProductionOrderFindPrompt extends RoutePrompt
{
    protected string $name = 'production_order_find';

    protected string $title = 'Consultar una orden de producción';

    protected string $description = 'Consultar una orden de producción';

    protected string $method = 'GET';

    protected string $path = '/technical_sheets/production_orders/find/{id}';

    protected string $business = 'Trae una orden de producción, es decir un lote (referencia más corte), por su id numérico, con todo su detalle. Úsala cuando la persona pregunte «muéstrame el lote BO001-A» y ya tenga el id de la orden. Devuelve la orden con su ficha técnica (producto con marca, grupo, tallas, subcategoría y categoría, tipo de prenda, subgrupo, colección y fotos), los detalles con la curva por talla de cada destino, la tela, el color, las piezas, los rollos de tela con su tipo de insumo, las reasignaciones recibidas desde otros lotes y los derivados. También trae el catálogo de estados de orden. El estado de la orden puede ser Pendiente, Aprobado o Cancelado. Para buscar el lote por texto se usa la lista de órdenes de producción, no esta ruta.';
}
