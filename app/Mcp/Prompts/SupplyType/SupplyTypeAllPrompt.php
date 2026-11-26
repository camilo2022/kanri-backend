<?php

namespace App\Mcp\Prompts\SupplyType;

use App\Mcp\Prompts\RoutePrompt;

class SupplyTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'supply_type_all';

    protected string $title = 'Listar los tipos de insumo';

    protected string $description = 'Listar los tipos de insumo';

    protected string $method = 'GET';

    protected string $path = '/typification/supply_types/all';

    protected string $business = 'Lista los tipos de insumo (por ejemplo tela, hilo, botón, cremallera), que agrupan los insumos de la confección. Úsala cuando la persona pregunte «¿qué tipos de insumo hay?» o «¿qué insumos tiene el tipo X?». Cada tipo trae por defecto su lista de insumos, salvo con without_supplies verdadero, que los omite porque pueden ser miles. Filtros propios: in_technical_sheet e in_production_order, para quedarse con los tipos que se usan en la ficha técnica o en la orden de producción según su configuración. with_trashed es obligatorio. Admite búsqueda, orden y paginación; sin per_page devuelve todos. También la consultan quienes configuran procesos, subprocesos u operaciones. Para listar los insumos de un solo tipo con su búsqueda se usa la consulta de insumos de un tipo.';
}
