<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailMovementPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_movement';

    protected string $title = 'Productividad de un movimiento';

    protected string $description = 'Productividad de un movimiento';

    protected string $method = 'GET';

    protected string $path = '/productivity/details/movement/{movement_id}';

    protected string $business = 'Muestra la productividad de un movimiento: los registros de trabajo ya iniciados del lote en los puestos del proceso de ese movimiento, de todos los turnos, con las unidades que le tocaron a ese destino. En procesos por unidades son los +1 contados contra los ingresos aprobados, e incluye other_units con lo del mismo registro en otros destinos; en Corte son los ingresos que el registro creó para ese destino; en otro caso es null. También trae lo recibido por el destino, es decir los ingresos aprobados, y el tipo de medición. Úsala para «¿cuánto se ha trabajado de este lote en este proceso?». El id del movimiento es entero y el movimiento debe ser de un proceso con referencia; si no, responde error de validación. No consulta los traslados del movimiento: para eso está la consulta de movimientos. Solo lee.';
}
