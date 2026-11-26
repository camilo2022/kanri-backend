<?php

namespace App\Mcp\Prompts\ManagementProduction;

use App\Mcp\Prompts\RoutePrompt;

class ManagementProductionFindPrompt extends RoutePrompt
{
    protected string $name = 'management_production_find';

    protected string $title = 'Obtener una colección con sus fichas y órdenes de producción para programar';

    protected string $description = 'Obtener una colección con sus fichas y órdenes de producción para programar';

    protected string $method = 'GET';

    protected string $path = '/management/production/find/{id}';

    protected string $business = 'Es la consulta de la pantalla Gestión de producción: trae una colección por su id numérico con sus fichas técnicas que ya no están en estado Pendiente, cada una con su producto (marca con tallas, subcategoría y categoría), fotos, productos STARA asociados y sus órdenes de producción (lotes) con curvas por talla, tela, color, proveedor y reasignaciones. Úsala cuando la persona pregunte «¿qué lotes tiene la colección X?» o «¿qué lotes están listos para programar?». Admite el parámetro search para filtrar fichas por código de referencia o por referencia con lote, por ejemplo BO123589-B (el lote se arma como código del producto, un guion y el corte). Además devuelve ready_references (por proceso, los lotes listos para ponerles fecha: la ficha tiene el proceso En revisión o Aprobado, el lote no está cancelado y no tiene fecha; después del primer proceso también exige un ingreso aprobado), unworked_dates (fechas ya vencidas en las que el lote no se trabajó), worked_processes (procesos donde el lote ya tiene trabajo y su fecha queda bloqueada) y los catálogos de estados y prioridad.';
}
