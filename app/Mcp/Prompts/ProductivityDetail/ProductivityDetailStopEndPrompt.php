<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailStopEndPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_stop_end';

    protected string $title = 'Terminar la parada no programada en curso';

    protected string $description = 'Terminar la parada no programada en curso';

    protected string $method = 'POST';

    protected string $path = '/productivity/details/workstation/{token}/stop_end';

    protected string $business = 'Acción de la tablet del operario (ciclo: start, +1/−1, stop/stop_end, finish). Termina ahora la parada no programada en curso para que el grupo retome el trabajo. Se rechaza si el puesto no tiene trabajo en curso o no hay una parada que terminar; las paradas programadas terminan solas y las de espera de unidades las cierra el sistema, así que esas no se terminan a mano. Efecto: guarda la hora de fin en la parada del registro y de sus acompañantes con parada manual abierta, y reevalúa las esperas automáticas: puede abrir o cerrar la espera de unidades si el puesto anterior aún no soltó prendas. La duración queda en settings de la parada y afecta el tiempo efectivo que calcula el frontend. La IA debe confirmar que el trabajo se reanuda. No se puede deshacer; se puede registrar otra parada con stop.';
}
