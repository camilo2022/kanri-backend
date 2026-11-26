<?php

namespace App\Mcp\Prompts\StopType;

use App\Mcp\Prompts\RoutePrompt;

class StopTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'stop_type_all';

    protected string $title = 'Listar los tipos de parada';

    protected string $description = 'Listar los tipos de parada';

    protected string $method = 'GET';

    protected string $path = '/typification/stop_types/all';

    protected string $business = 'Lista los tipos de parada (paro) del catálogo de Kanri. Los tipos clasifican las paradas que se registran en el trabajo de producción; según la documentación del proyecto, las paradas son programadas (por ejemplo desayuno o almuerzo) o no programadas (por ejemplo falta de insumos). Sirve para «¿qué tipos de parada existen?» o para obtener el id de un tipo. Admite búsqueda por texto, orden, paginación con per_page y page, y with_trashed para incluir los inactivos; sin per_page devuelve todos. Cada tipo trae su ítem del catálogo. No lista las paradas de cada tipo: para eso está la consulta de paradas de un tipo. Solo consulta.';
}
