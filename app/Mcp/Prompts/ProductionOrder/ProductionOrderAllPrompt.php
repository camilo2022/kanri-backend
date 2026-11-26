<?php

namespace App\Mcp\Prompts\ProductionOrder;

use App\Mcp\Prompts\RoutePrompt;

class ProductionOrderAllPrompt extends RoutePrompt
{
    protected string $name = 'production_order_all';

    protected string $title = 'Listar las órdenes de producción de una ficha técnica';

    protected string $description = 'Listar las órdenes de producción de una ficha técnica';

    protected string $method = 'GET';

    protected string $path = '/technical_sheets/production_orders/all/{technical_sheet_id?}';

    protected string $business = 'Lista las órdenes de producción, es decir los lotes (referencia más corte, como BO001-A). Con technical_sheet_id en la ruta trae solo los lotes de esa ficha; sin él, trae los de todas. Úsala cuando la persona pregunte «¿qué lotes tiene la referencia X?», «¿qué lotes están programados esta semana?» o «¿qué lotes se pueden programar en el proceso Y?». La búsqueda de texto solo mira el consecutivo y el corte, no el código de la referencia. Filtros propios: processes_id, start_date y end_date (fechas programadas en esos procesos), has_programations, with_typologies (agrega la tipología de cada proceso en la ficha), process_id con model_type y model_id (lotes programables en ese puesto), chain y pending_previous (programación en cadena). with_trashed es obligatorio. Sin ficha ni process_id, start_date y end_date son obligatorias. Sin per_page devuelve todo en una página. Cada orden trae su uuid, ficha con producto y tipo de prenda, detalles con cantidades por talla, tela, piezas y proveedor.';
}
