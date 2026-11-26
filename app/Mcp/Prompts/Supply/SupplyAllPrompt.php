<?php

namespace App\Mcp\Prompts\Supply;

use App\Mcp\Prompts\RoutePrompt;

class SupplyAllPrompt extends RoutePrompt
{
    protected string $name = 'supply_all';

    protected string $title = 'Listar los insumos de un tipo de insumo';

    protected string $description = 'Listar los insumos de un tipo de insumo';

    protected string $method = 'GET';

    protected string $path = '/typification/supply_types/supplies/all/{supply_type_id}';

    protected string $business = 'Lista los insumos de un tipo de insumo (por ejemplo las telas, o los hilos), identificado por su id numérico en la ruta. Úsala cuando la persona diga «¿qué telas hay?» o busque un insumo concreto de un tipo. Cada insumo trae su tipo, su configuración y los detalles de órdenes de producción donde se usa. Con supply_id se queda solo con los insumos relacionados con ese insumo (por ejemplo los rollos de una tela cuando se asignan a un lote). Admite búsqueda, orden y paginación; sin per_page devuelve todos en una página; with_trashed es obligatorio e incluye los desactivados. También la consultan quienes configuran procesos, subprocesos u operaciones. No trae inventario ni existencias.';
}
