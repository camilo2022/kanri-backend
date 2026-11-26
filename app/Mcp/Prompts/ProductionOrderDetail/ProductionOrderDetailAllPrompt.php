<?php

namespace App\Mcp\Prompts\ProductionOrderDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductionOrderDetailAllPrompt extends RoutePrompt
{
    protected string $name = 'production_order_detail_all';

    protected string $title = 'Listar curvas de lotes para elegir el destino de un traslado';

    protected string $description = 'Listar curvas de lotes para elegir el destino de un traslado';

    protected string $method = 'GET';

    protected string $path = '/movements/production_order_details/all';

    protected string $business = 'Lista detalles de órdenes de producción para elegir el lote destino de un traslado de unidades. Cada detalle trae su orden de producción (el lote) y el elemento al que apunta; cuando es una curva de producto, ese elemento es el producto con su marca y tallas. Úsala cuando la persona pregunte «¿a qué lote puedo trasladar estas unidades?». Recibe has_destination (obligatorio, verdadero o falso): con verdadero solo trae los detalles que tienen destino, es decir las curvas, y deja por fuera telas, piezas y rollos. Con production_order_detail_id se excluye ese detalle de la lista (el de origen del traslado). No tiene búsqueda ni paginación: devuelve todos los que cumplan. No registra el traslado.';
}
