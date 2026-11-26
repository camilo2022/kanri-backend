<?php

namespace App\Mcp\Prompts\Operation;

use App\Mcp\Prompts\RoutePrompt;

class OperationSettingPrompt extends RoutePrompt
{
    protected string $name = 'operation_setting';

    protected string $title = 'Configurar el formulario de una operación';

    protected string $description = 'Configurar el formulario de una operación';

    protected string $method = 'PUT';

    protected string $path = '/workflow/processes/subprocesses/operations/setting/{id}';

    protected string $business = 'Guarda la configuración avanzada de una operación: el esquema (settings.schema) con la tabla estática (header con label, colspan y rowspan; body con celdas th o td) que se llena en la ficha técnica para esa operación. Se rechaza si falta el schema o la estructura estática es inválida. Efecto: el controlador reemplaza todo settings por lo enviado, así que claves como in_technical_sheet, in_production_order, productivity_measurement, time_multiplier, units_entry, schedule_units y color se pierden si no se reenvían; el uuid del QR se conserva. No se pudo confirmar qué envía el frontend. Esas claves determinan cómo se mide, programa y cuenta el puesto. La IA debe confirmar el contenido completo. Reversible reenviando settings.';
}
