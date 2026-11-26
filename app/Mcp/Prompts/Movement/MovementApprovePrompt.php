<?php

namespace App\Mcp\Prompts\Movement;

use App\Mcp\Prompts\RoutePrompt;

class MovementApprovePrompt extends RoutePrompt
{
    protected string $name = 'movement_approve';

    protected string $title = 'Aprobar un traslado (proceso que recibe)';

    protected string $description = 'Aprobar un traslado (proceso que recibe)';

    protected string $method = 'POST';

    protected string $path = '/movements/approve';

    protected string $business = 'El proceso que RECIBE acepta un traslado. El traslado debe estar en estado «Pendiente» (si no, se rechaza), debe pertenecer al movimiento de origen indicado y la cantidad total aceptada no puede superar la enviada. Lógica: por cada talla se guarda la cantidad realmente recibida; si se recibe menos de lo enviado en una talla, se descuenta esa diferencia de las redistribuciones de esa talla (en orden de creación); el traslado pasa a «Aprobado» y se guarda la observación (si no se envía, queda vacía). Al cambiar de estado se recalcula el estado del movimiento de origen (Ingreso, Movimiento o Salida) y se reconstruye la curva por talla del lote: las unidades aprobadas entran al destino. El ingreso al primer proceso (Corte) solo puede venir de Productividad (un registro de trabajo), no de otro proceso. Hallazgo: se valida el total, no cada talla por separado. Antes de ejecutar, confirmar con la persona qué cantidad llegó por talla y la observación si llegó menos. No existe una ruta para deshacer una aprobación; el efecto no se puede revertir desde la API.';
}
