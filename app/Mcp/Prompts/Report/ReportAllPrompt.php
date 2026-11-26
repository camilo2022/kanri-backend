<?php

namespace App\Mcp\Prompts\Report;

use App\Mcp\Prompts\RoutePrompt;

class ReportAllPrompt extends RoutePrompt
{
    protected string $name = 'report_all';

    protected string $title = 'Listar los reportes';

    protected string $description = 'Listar los reportes';

    protected string $method = 'GET';

    protected string $path = '/reports/all';

    protected string $business = 'Lista los reportes configurados en Kanri. Un reporte define un modelo base, las columnas que se pueden elegir y los filtros disponibles, y se ejecuta con la ruta de generar. Sirve para «¿qué reportes hay?» o para encontrar el id de un reporte antes de generarlo. Admite búsqueda por texto, orden y paginación con per_page y page; sin per_page devuelve todos, y with_trashed incluye los inactivos. Cada reporte trae su ítem del catálogo y los permisos asignados al reporte. Ojo: ver la lista no da derecho a generar un reporte, porque cada reporte se autoriza aparte con su propio permiso. Solo consulta; no calcula datos.';
}
