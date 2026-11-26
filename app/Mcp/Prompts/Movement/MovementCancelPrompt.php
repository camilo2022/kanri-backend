<?php

namespace App\Mcp\Prompts\Movement;

use App\Mcp\Prompts\RoutePrompt;

class MovementCancelPrompt extends RoutePrompt
{
    protected string $name = 'movement_cancel';

    protected string $title = 'Cancelar un traslado (proceso que envía)';

    protected string $description = 'Cancelar un traslado (proceso que envía)';

    protected string $method = 'POST';

    protected string $path = '/movements/cancel';

    protected string $business = 'El proceso que ENVÍA anula un traslado antes de que el destino lo reciba. Solo usa movement_detail_id y la observación. El traslado debe estar en estado «Pendiente»; si ya fue aprobado, rechazado o cancelado, la petición se rechaza. Efecto: el traslado pasa a «Cancelado» con su observación; no cambia cantidades ni redistribuciones. Al cambiar de estado se recalcula el estado del movimiento de origen (Ingreso, Movimiento o Salida) y se reconstruye la curva por talla del lote: las unidades que estaban reservadas como pendientes de salida vuelven al proceso origen. El código no distingue por rol entre quien envía y quien recibe: lo que separa cancelar de rechazar es el permiso (movements.cancel frente a movements.decline). Antes de ejecutar, confirmar cuál traslado y el motivo. Es definitivo: no hay ruta para reactivarlo; si se necesita de nuevo, se crea otro con «movements/save».';
}
