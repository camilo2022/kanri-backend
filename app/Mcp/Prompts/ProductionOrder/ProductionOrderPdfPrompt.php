<?php

namespace App\Mcp\Prompts\ProductionOrder;

use App\Mcp\Prompts\RoutePrompt;

class ProductionOrderPdfPrompt extends RoutePrompt
{
    protected string $name = 'production_order_pdf';

    protected string $title = 'Generar el PDF de una orden de producción desde el sistema';

    protected string $description = 'Generar el PDF de una orden de producción desde el sistema';

    protected string $method = 'GET';

    protected string $path = '/technical_sheets/production_orders/download_pdf/{uuid}';

    protected string $business = 'Prepara el PDF de la orden de producción (el documento del lote que se imprime para trabajar, con su ficha, curva por destino, tela, rollos, piezas, reasignaciones y trazos). No entrega el archivo sino una dirección temporal firmada que vence a los 5 minutos y que termina en el nombre PO seguido de la referencia y el corte. Úsala cuando la persona pida «el PDF de la orden de producción del lote BO001-A» o «imprímeme la orden». El uuid es el de la orden de producción (se ve en la lista de órdenes). Es distinto del PDF de la ficha técnica del lote, que usa el mismo uuid pero otro diseño. No modifica nada.';
}
