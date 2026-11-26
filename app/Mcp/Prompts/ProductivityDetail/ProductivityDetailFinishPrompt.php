<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailFinishPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_finish';

    protected string $title = 'Finalizar el trabajo en curso';

    protected string $description = 'Finalizar el trabajo en curso';

    protected string $method = 'POST';

    protected string $path = '/productivity/details/workstation/{token}/finish';

    protected string $business = 'Acción de la tablet del operario (ciclo: start, +1/−1, stop/stop_end, finish). Finaliza el trabajo en curso y fija su fin oficial: la hora actual o, si ya pasó, el fin del turno. Se rechaza si no hay trabajo en curso, si hay una parada manual abierta (programada: esperar a que termine), si el puesto digita el total y falta units o supera lo disponible del lote, o, en el último puesto de la cadena del primer proceso (crea ingresos), si faltan destinos y tallas válidos de la orden o la suma es cero. En los demás puestos no se envían cantidades. Efecto: guarda el fin (los minutos y la eficiencia los calcula el frontend), cierra paradas de espera, finaliza a los acompañantes, y si hay cantidades crea el movimiento de Ingreso al siguiente proceso con detalles Pendientes por destino y talla. La IA debe confirmar unidades. No es reversible desde esta API.';
}
