<?php

namespace App\Mcp\Prompts\Report;

use App\Mcp\Prompts\RoutePrompt;

class ReportFindPrompt extends RoutePrompt
{
    protected string $name = 'report_find';

    protected string $title = 'Obtener un tipo de trasero específico';

    protected string $description = 'Obtener un tipo de trasero específico';

    protected string $method = 'GET';

    protected string $path = '/reports/find/{id}';

    protected string $business = 'Trae un reporte por su id numérico con su configuración completa: nombre, descripción, los campos y filtros configurados en settings, el permiso propio del reporte y su ítem del catálogo. Además devuelve los catálogos que ayudan a armar los filtros: los modelos disponibles, los estados de ficha técnica y de orden de producción, los estados de proceso y los destinos de producción. Úsala antes de generar un reporte, para saber qué columnas y filtros acepta, con frases como «¿qué filtros tiene el reporte de trazabilidad?». El id debe corresponder a un reporte existente. No genera los datos del reporte; para eso está la ruta de generar. Solo consulta.';
}
