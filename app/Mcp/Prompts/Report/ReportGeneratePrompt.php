<?php

namespace App\Mcp\Prompts\Report;

use App\Mcp\Prompts\RoutePrompt;

class ReportGeneratePrompt extends RoutePrompt
{
    protected string $name = 'report_generate';

    protected string $title = 'Generar un reporte';

    protected string $description = 'Generar un reporte';

    protected string $method = 'POST';

    protected string $path = '/reports/{id}/generate';

    protected string $business = 'Genera un reporte: calcula y devuelve los datos sin guardar nada. El id del reporte es entero. El cuerpo lleva el nombre de la clase del modelo del reporte (debe estar en App\\Models), fields con al menos una columna a mostrar, filters opcional con los filtros configurados del reporte (cada uno como una lista de valores; los de tipo rango de fechas llevan from y to, y un filtro que no exista en el reporte se rechaza) y la página: page y per_page (máximo 200, 50 por defecto). Con page devuelve solo esa página más el total y los datos de paginación; sin page devuelve todos los registros. Los reportes calculados por servicio (trazabilidad o productividad) arman sus filas por lote. Cada reporte se autoriza con su propio permiso mediante ReportPolicy, que solo mira permisos asignados directamente al usuario y no los del rol; sin ese permiso responde que no tiene permisos. Úsala con «genera el reporte de…». No genera el Excel.';
}
