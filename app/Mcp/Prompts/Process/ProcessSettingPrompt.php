<?php

namespace App\Mcp\Prompts\Process;

use App\Mcp\Prompts\RoutePrompt;

class ProcessSettingPrompt extends RoutePrompt
{
    protected string $name = 'process_setting';

    protected string $title = 'Configurar el formulario de un proceso';

    protected string $description = 'Configurar el formulario de un proceso';

    protected string $method = 'PUT';

    protected string $path = '/workflow/processes/setting/{id}';

    protected string $business = 'Guarda la configuración avanzada de un proceso: el esquema (settings.schema) con las tablas de datos que se llenan en la ficha técnica, dinámica (columnas con campo en minúsculas y guiones bajos, etiqueta en mayúsculas, tipo y reglas como required, min, max, regex, after, before) y estática (celdas con rowspan y colspan). Se rechaza si falta el schema, si hay campos o etiquetas repetidos, o si min, max, regex o fechas de las reglas son inválidos. Efecto: el controlador reemplaza todo settings por lo enviado, por lo que si no se reenvían las demás claves (subprocesses, subprocess_flow, in_production_order, productivity_measurement, any_workstation, reprocesses, paragraph, color) se pierden; el uuid del QR se conserva. No se pudo confirmar qué envía el frontend. Esas claves gobiernan las reglas del flujo. La IA debe confirmar el contenido completo. Reversible reenviando settings.';
}
