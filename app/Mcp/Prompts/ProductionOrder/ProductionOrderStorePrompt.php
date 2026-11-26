<?php

namespace App\Mcp\Prompts\ProductionOrder;

use App\Mcp\Prompts\RoutePrompt;

class ProductionOrderStorePrompt extends RoutePrompt
{
    protected string $name = 'production_order_store';

    protected string $title = 'Crear una orden de producción';

    protected string $description = 'Crear una orden de producción';

    protected string $method = 'POST';

    protected string $path = '/technical_sheets/production_orders/store';

    protected string $business = 'Crea una orden de producción (lote) para una ficha técnica con eficiencia, corte, tela, color, ancho, indicador de tela de bolsillo, observación, estado, lugar de producción, curva por destino, piezas, rollos y los trazos (settings). Se rechaza si ya existe un lote con ese corte en la ficha (aquí solo se exige que sea único, no consecutivo), si el color no está asociado a la tela, si el tipo de insumo no es apto para órdenes, si falta la curva de un destino (NACIONAL, MEDELLIN o STARA) o hay más de un destino sin especificación por talla, si la cantidad de un rollo supera los metros disponibles (metros del rollo menos lo usado en otras órdenes), o si la pieza no trae cantidad. El lugar SATELITE exige proveedor. El PDF de trazos es opcional y debe llamarse «código-de-producto-corte»; también puede copiarse el de otra orden con strokes_file_id. Efectos: consecutivo y uuid nuevos, color asociado, detalles y cantidades por talla; con reasigned_curve se descuentan unidades de otras órdenes de origen (no empezadas ni programadas) por talla, sin dejar negativos, y se deja registro del origen. Confirmar ficha, corte y curvas. No hay deshacer directo.';
}
