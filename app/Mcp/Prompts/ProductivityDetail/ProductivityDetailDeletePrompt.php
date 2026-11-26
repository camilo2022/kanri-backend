<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailDeletePrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_delete';

    protected string $title = 'Quitar un registro programado';

    protected string $description = 'Quitar un registro programado';

    protected string $method = 'DELETE';

    protected string $path = '/productivity/details/delete/{id}';

    protected string $business = 'Quita un registro programado de un turno. Se rechaza si el registro ya fue iniciado en la estación (tiene hora de escaneo) o si el turno ya terminó; no valida otros estados. Efecto: desactiva el registro (borrado lógico, auditado) y rehace el reparto de unidades del lote entre las filas restantes del puesto, de modo que las demás filas pueden recibir más unidades de meta. La IA debe confirmar referencia, puesto y hora del registro que se quita. Es reversible solo volviendo a programarlo con la ruta de programar, no existe ruta de restauración de registros.';
}
