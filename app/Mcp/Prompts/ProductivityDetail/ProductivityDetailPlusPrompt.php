<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailPlusPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_plus';

    protected string $title = 'Sumar una unidad (+1)';

    protected string $description = 'Sumar una unidad (+1)';

    protected string $method = 'POST';

    protected string $path = '/productivity/details/workstation/{token}/plus';

    protected string $business = 'Acción de la tablet del operario (ciclo: start, +1/−1, stop/stop_end, finish). Suma una unidad terminada al trabajo en curso de un puesto medido por unidades con conteo por +1. Se rechaza si no hay trabajo en curso, si el puesto se mide por lote o registra sus unidades al finalizar, si el turno terminó, si hay una parada manual en curso, si el lote ya está completo en el paso, o si ya se hicieron todas las unidades que el puesto programado antes soltó. Efecto: suma 1 en settings.produced del registro (ingreso recibido más antiguo con unidades pendientes), guarda la hora del +1 (para la meta por hora), lo aplica a los acompañantes que aún puedan sumar y reevalúa la espera automática. La IA debe confirmar el puesto y registro. Reversible con minus, que solo quita unidades de ese mismo registro.';
}
