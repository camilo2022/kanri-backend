<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailStartPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_start';

    protected string $title = 'Iniciar trabajo en un puesto';

    protected string $description = 'Iniciar trabajo en un puesto';

    protected string $method = 'POST';

    protected string $path = '/productivity/details/workstation/{token}/start';

    protected string $business = 'Acción de la tablet del operario. El token es el QR del puesto (proceso, subproceso u operación sin hijos). Ciclo completo: iniciar (esta ruta) → +1/−1 (plus, minus) o total al finalizar → paradas (stop, stop_end) → finalizar (finish). Al iniciar se escanea la orden y se eligen empleados. Se rechaza si no hay turno abierto del proceso a esta hora o el turno terminó; si el puesto ya tiene un trabajo en curso (los puestos programables admiten varios grupos); si no tiene medición definida; si la ficha del proceso está Pendiente; si la ficha no lleva el puesto; si no hay regla estándar o capacidad para esas personas; si el lote no ha llegado (sin ingresos aprobados) o ya está completo; si no está programado o no es su hora; si los puestos anteriores de la cadena no terminaron o no soltaron unidades; o si los empleados no son el grupo programado (solo se reemplaza al ocupado) o están ocupados. Efecto: crea o retoma el registro con la hora real o programada, la hora de escaneo, la capacidad de la regla y los empleados, e inicia a los acompañantes del mismo grupo. La IA debe confirmar puesto, orden y empleados.';
}
