<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailUpdatePrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_update';

    protected string $title = 'Editar un registro programado';

    protected string $description = 'Editar un registro programado';

    protected string $method = 'PUT';

    protected string $path = '/productivity/details/update/{id}';

    protected string $business = 'Edita un registro programado: puesto, referencia, inicio, empleados y, según el puesto, fin, cantidad multiplicadora o unidades de la tanda. Aplica las mismas reglas que programarlo (sin contarse a sí mismo). Se rechaza si el registro ya fue iniciado en la estación (tiene escaneo), si el turno está desactivado o terminó, si el inicio cae fuera del turno o en el pasado con el turno En curso, o si hay cruces de horario, empleados ocupados o ficha técnica Pendiente. Efecto: reemplaza empleados, recalcula la regla y rehace el reparto de unidades del lote en el puesto nuevo y, si cambió de referencia, también en la anterior. La IA debe confirmar los cambios. Reversible editando de nuevo, mientras no se escanee.';
}
