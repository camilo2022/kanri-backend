<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailMinusPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_minus';

    protected string $title = 'Quitar una unidad (−1)';

    protected string $description = 'Quitar una unidad (−1)';

    protected string $method = 'POST';

    protected string $path = '/productivity/details/workstation/{token}/minus';

    protected string $business = 'Acción de la tablet del operario (ciclo: start, +1/−1, stop/stop_end, finish). Resta una unidad por si se tocó +1 de más. Se rechaza si no hay trabajo en curso, si el puesto no cuenta con +1 (por lote o total al finalizar) o si el registro, y sus acompañantes, no han sumado ninguna unidad: solo se quitan unidades que sumó ese mismo registro, nunca las de otros. Efecto: resta 1 en settings.produced del último ingreso con unidades hechas (sin bajar de cero), elimina la última hora de +1 registrada y reevalúa la espera automática; con acompañantes, se quita en quienes llevan más unidades. La IA debe confirmar el registro. Reversible sumando de nuevo con plus, siempre que el turno siga abierto, no haya parada y al lote le queden unidades.';
}
