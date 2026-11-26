<?php

namespace App\Mcp\Prompts\Movement;

use App\Mcp\Prompts\RoutePrompt;

class MovementDeclinePrompt extends RoutePrompt
{
    protected string $name = 'movement_decline';

    protected string $title = 'Rechazar un traslado (proceso que recibe)';

    protected string $description = 'Rechazar un traslado (proceso que recibe)';

    protected string $method = 'POST';

    protected string $path = '/movements/decline';

    protected string $business = 'El proceso que RECIBE rechaza un traslado que no acepta. Solo usa movement_detail_id y la observación (motivo). El traslado debe estar en estado «Pendiente»; si está Aprobado, Rechazado o Cancelado, se rechaza la petición. Efecto: el traslado pasa a «Rechazado» con la observación guardada. Como los traslados rechazados dejan de contar como enviados, al cambiar el estado se recalcula el estado del movimiento de origen (puede volver a Ingreso o Movimiento) y se reconstruye la curva por talla del lote, de modo que las unidades quedan otra vez disponibles en el proceso origen. No modifica cantidades ni redistribuciones del traslado. Los permisos exigidos son distintos a los de aprobar y cancelar. Antes de ejecutar, confirmar cuál traslado (proceso origen, destino y cantidades) y el motivo. Es definitivo: no hay ruta para reabrir un traslado rechazado; habría que crear uno nuevo con «movements/save».';
}
