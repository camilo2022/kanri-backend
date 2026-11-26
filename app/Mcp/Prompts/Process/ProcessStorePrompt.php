<?php

namespace App\Mcp\Prompts\Process;

use App\Mcp\Prompts\RoutePrompt;

class ProcessStorePrompt extends RoutePrompt
{
    protected string $name = 'process_store';

    protected string $title = 'Crear un proceso';

    protected string $description = 'Crear un proceso';

    protected string $method = 'POST';

    protected string $path = '/workflow/processes/store';

    protected string $business = 'Crea un proceso del flujo de producción (por ejemplo Corte, Lavandería), con descripción en mayúsculas, procesos posteriores y configuración en settings. Se rechaza si el nombre ya existe, si el color (#RRGGBB) ya lo usa otro proceso, subproceso u operación, si se marca un segundo proceso como lugar de producción (paragraph production_place) en la orden de producción, o si falta la medición productiva cuando aplica a la orden. Claves de settings: subprocesses y subprocess_flow (sequence o parallel), in_technical_sheet, in_production_order, productivity_measurement (lots o units), any_workstation (cualquier puesto trabaja la referencia), reprocesses, paragraph y color. Efecto: guarda el proceso y enlaza los procesos posteriores, que definen la línea y cuál es el primero. La IA debe confirmar todos esos valores. Reversible: se edita o desactiva.';
}
