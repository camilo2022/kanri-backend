<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailStopStorePrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_stop_store';

    protected string $title = 'Registrar una parada';

    protected string $description = 'Registrar una parada';

    protected string $method = 'POST';

    protected string $path = '/productivity/details/workstation/{token}/stop';

    protected string $business = 'Acción de la tablet del operario (ciclo: start, +1/−1, stop/stop_end, finish). Registra una parada en el trabajo en curso del puesto, elegida del catálogo de paradas: la programada termina sola con la duración del catálogo; la no programada queda abierta hasta stop_end. Se rechaza si el puesto no tiene trabajo en curso, si el turno ya terminó (solo se puede finalizar), si se elige la parada de espera de unidades (la gestiona el sistema), si la programada no tiene duración en el catálogo si ya hay otra parada manual en curso o si la programada terminaría después del fin del turno. Efecto: cierra la espera automática si estaba abierta, crea la parada con nombre, tipo e inicio en el registro y la aplica también a los acompañantes del mismo grupo. Mientras hay parada no se puede sumar +1 ni finalizar. Los minutos perdidos los calcula el frontend. La IA debe confirmar el motivo. Reversible con stop_end (no programadas).';
}
