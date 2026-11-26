<?php

namespace App\Mcp\Prompts\ProductionOrder;

use App\Mcp\Prompts\RoutePrompt;

class ProductionOrderUpdatePrompt extends RoutePrompt
{
    protected string $name = 'production_order_update';

    protected string $title = 'Editar una orden de producción';

    protected string $description = 'Editar una orden de producción';

    protected string $method = 'PUT';

    protected string $path = '/technical_sheets/production_orders/update/{id}';

    protected string $business = 'Edita una orden de producción existente: datos, color, detalles, piezas, rollos y trazo (si llega un PDF se agrega como documento nuevo, sin comparar su nombre). Se rechaza si el corte ya existe en otra orden de la misma ficha, si el color no está asociado a la tela (salvo que la orden ya tuviera esa tela y color), por las mismas reglas de curva, rollos (la cantidad disponible no cuenta la propia orden) y piezas de la creación, y si el lote ya se empezó a trabajar o está programado y la edición quitaría un destino, piezas o rollos. Efectos: lo que la edición quita se aplica así: el destino con curva en cero queda en 0 (no se borra) y las piezas o rollos que ya no llegan se eliminan; con reasigned_curve se descuentan unidades de otras órdenes origen. El estado (Pendiente, Aprobado, Cancelado) se asigna sin validar transiciones. Confirmar qué se quitará y el efecto sobre otras órdenes si hay reasignación. Las piezas y rollos eliminados y los descuentos de curva no se restauran desde la API.';
}
