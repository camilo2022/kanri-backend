<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailStorePrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_store';

    protected string $title = 'Programar un registro de trabajo';

    protected string $description = 'Programar un registro de trabajo';

    protected string $method = 'POST';

    protected string $path = '/productivity/details/store';

    protected string $business = 'Programa un registro de trabajo en un turno: una referencia (orden de producción) en un puesto, con hora de inicio HH:MM y los empleados que lo harán; la hace el líder antes o durante el turno. Se rechaza si el turno terminó, si el puesto no es del proceso o no tiene medición (por lote o unidades), si la ficha técnica del proceso sigue en Pendiente (debe estar En revisión o Aprobado), si la ficha no lleva ese puesto, si el lote no llegó al proceso o ya está completo, si el inicio queda fuera del turno o en el pasado con el turno En curso, si faltan fin (por lote), cantidad multiplicadora o unidades de la tanda, o si el grupo se cruza con otro trabajo. Efecto: crea el registro con su regla estándar, guarda empleados (marca los prestados de otros puestos) y rehace el reparto de unidades del lote entre sus filas. La IA debe confirmar referencia, puesto, hora, fin y empleados. Reversible mientras no se escanee.';
}
