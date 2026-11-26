<?php

namespace App\Mcp\Prompts\Report;

use App\Mcp\Prompts\RoutePrompt;

class ReportExportPrompt extends RoutePrompt
{
    protected string $name = 'report_export';

    protected string $title = 'Report Export';

    protected string $description = 'Report Export';

    protected string $method = 'POST';

    protected string $path = '/reports/{id}/export';

    protected string $business = 'Genera un Excel (report.xlsx) con el reporte elegido, con las mismas columnas y filtros que Generar reporte (campos fields y filtros filters). No guarda nada en Kanri: la respuesta trae un enlace temporal para descargar el archivo. Úsala cuando la persona pida el reporte en Excel; si solo quiere verlo en el chat, usa Generar reporte.';
}
