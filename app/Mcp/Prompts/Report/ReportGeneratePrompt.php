<?php

namespace App\Mcp\Prompts\Report;

use App\Mcp\Prompts\RoutePrompt;
use App\Models\Report;
use Laravel\Mcp\Request;

/**
 * Este prompt se arma con la configuración real del reporte (columnas y filtros que guarda en settings), así que un
 * reporte nuevo o modificado se explica solo, sin regenerar archivos. mcp:make-prompts --force lo sobrescribiría.
 */
class ReportGeneratePrompt extends RoutePrompt
{
    protected string $name = 'report_generate';

    protected string $title = 'Generar un reporte';

    protected string $description = 'Generar un reporte';

    protected string $method = 'POST';

    protected string $path = '/reports/{id}/generate';

    protected string $business = 'Genera un reporte: calcula y devuelve los datos sin guardar nada. El id del reporte es entero. El cuerpo lleva el nombre de la clase del modelo del reporte (debe estar en App\\Models), fields con al menos una columna a mostrar, filters opcional con los filtros configurados del reporte (cada uno como una lista de valores; los de tipo rango de fechas llevan from y to, y un filtro que no exista en el reporte se rechaza) y la página: page y per_page (máximo 200, 50 por defecto). Con page devuelve solo esa página más el total y los datos de paginación; sin page devuelve todos los registros. Los reportes calculados por servicio (trazabilidad o productividad) arman sus filas por lote. Cada reporte se autoriza con su propio permiso mediante ReportPolicy, que solo mira permisos asignados directamente al usuario y no los del rol; sin ese permiso responde que no tiene permisos. Úsala con «genera el reporte de…». No genera el Excel.';

    protected function lines(Request $request): array
    {
        $lines = parent::lines($request);
        $report = Report::find((int) $request->get('id'));

        $lines[] = '';
        $lines[] = '## Reporte seleccionado';

        if (! $report) {
            $lines[] = 'No existe un reporte con ese id. Consulta los reportes disponibles con read_data (GET /reports/all) y vuelve a pedir este prompt con el id correcto.';

            return $lines;
        }

        $settings = $report->settings;

        $lines[] = 'Nombre: '.$report->name.($report->description && $report->description !== $report->name ? ' — '.$report->description : '');
        $lines[] = ($settings->source ?? null)
            ? 'Tipo: reporte calculado por servicio ('.$settings->source.'): arma sus filas por lote y acepta las columnas y los filtros listados abajo.'
            : 'Modelo (va en el campo model): '.($settings->model ?? 'no definido');

        $lines[] = '';
        $lines[] = '### Columnas que puedes pedir en fields';
        $lines[] = 'Cada columna se envía como objeto {"data_key": "...", "label": "..."} con los valores de esta lista:';

        foreach ($settings->fields ?? [] as $group) {
            $columns = [];

            foreach (data_get($group, 'fields', []) as $field) {
                $columns[] = data_get($field, 'label').' (data_key: '.data_get($field, 'data_key').')';
            }

            if ($columns) {
                $lines[] = '- '.data_get($group, 'label', 'Columnas').': '.implode('; ', $columns);
            }
        }

        $lines[] = '';
        $lines[] = '### Filtros que acepta (en filters, por la clave indicada)';
        $lines[] = 'Un filtro de tipo select se envía como una lista de valores (normalmente ids); uno de tipo date_range, como {"from": "AAAA-MM-DD", "to": "AAAA-MM-DD"}. Cualquier otro filtro se rechaza.';

        foreach ($settings->filters ?? [] as $key => $filter) {
            $type = data_get($filter, 'type', 'select');
            $source = data_get($filter, 'data');

            $lines[] = '- '.data_get($filter, 'label', $key).' — clave: "'.$key.'" — tipo: '.$type
                .($type === 'select' && $source ? ' — los valores salen del catálogo «'.$source.'»' : '')
                .(data_get($filter, 'calculated') ? ' — filtro calculado por el servicio' : '');
        }

        if ($settings->base_filters ?? null) {
            $lines[] = '';
            $lines[] = 'Este reporte ya aplica siempre estos filtros fijos (no los envíes): '.implode(', ', array_keys((array) $settings->base_filters)).'.';
        }

        if ($settings->dynamic_filters ?? null) {
            $lines[] = 'Además acepta estos parámetros sueltos en el cuerpo: '.implode(', ', array_keys((array) $settings->dynamic_filters)).'.';
        }

        $lines[] = '';
        $lines[] = 'Para filtrar por un valor que la persona nombra (por ejemplo una marca o una colección) y del que no conoces el id, búscalo primero con read_data en el listado del catálogo y, si hay varias coincidencias parecidas, pregúntale cuál quiere. Si no tienes permiso para ese listado, díselo.';

        return $lines;
    }
}
