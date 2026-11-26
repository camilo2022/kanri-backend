<?php

namespace App\Mcp\Prompts\Subprocess;

use App\Mcp\Prompts\RoutePrompt;

class SubprocessSettingPrompt extends RoutePrompt
{
    protected string $name = 'subprocess_setting';

    protected string $title = 'Configurar el formulario de un subproceso';

    protected string $description = 'Configurar el formulario de un subproceso';

    protected string $method = 'PUT';

    protected string $path = '/workflow/processes/subprocesses/setting/{id}';

    protected string $business = 'Guarda la configuración avanzada de un subproceso: el esquema (settings.schema) con tablas dinámica y estática que se llenan en la ficha técnica para ese subproceso (campos en minúsculas con guiones bajos, etiquetas en mayúsculas, tipos y reglas required, min, max, regex, after, before). Se rechaza si falta el schema, hay campos o etiquetas repetidos o reglas inválidas. Efecto: el controlador reemplaza todo settings por lo enviado, así que las demás claves (operations, operation_flow, in_technical_sheet, in_production_order, productivity_measurement, time_multiplier, units_entry, schedule_units, only_after_previous, next_conditions, color) se pierden si no se reenvían; el uuid del QR se conserva. No se pudo confirmar qué envía el frontend. La IA debe confirmar el contenido completo. Reversible reenviando settings.';
}
