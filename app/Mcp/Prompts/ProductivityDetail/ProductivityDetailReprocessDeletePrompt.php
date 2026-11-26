<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailReprocessDeletePrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_reprocess_delete';

    protected string $title = 'Anular un reproceso';

    protected string $description = 'Anular un reproceso';

    protected string $method = 'DELETE';

    protected string $path = '/productivity/details/reprocess/{id}/{index}';

    protected string $business = 'Anula un reproceso registrado por error, identificado por su posición (index, desde 0) dentro de los reprocesos del registro de trabajo. Se rechaza si esa posición no existe. Efecto: quita el elemento de settings.reprocesses y reindexa la lista; como el conteo de pasos depende de esos reprocesos, las unidades vuelven a considerarse hechas en los pasos afectados. No guarda quién lo anuló más allá de la auditoría del registro. La IA debe mostrar a la persona el reproceso (unidades, pasos, motivo) y confirmar cuál anular, porque al reindexarse las posiciones cambian. No es reversible directamente: habría que registrar el reproceso otra vez.';
}
