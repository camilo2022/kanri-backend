<?php

namespace App\Mcp\Prompts\Stop;

use App\Mcp\Prompts\RoutePrompt;

class StopAllPrompt extends RoutePrompt
{
    protected string $name = 'stop_all';

    protected string $title = 'Listar las paradas de un tipo de parada';

    protected string $description = 'Listar las paradas de un tipo de parada';

    protected string $method = 'GET';

    protected string $path = '/typification/stop_types/stops/all/{stop_type_id?}';

    protected string $business = 'Lista las paradas del catálogo, es decir las causas concretas de un paro durante el trabajo (por ejemplo desayuno, almuerzo, falta de insumos o paro mecánico). El id del tipo de parada es opcional y entero: con él trae solo las paradas de ese tipo, y sin él trae todas las paradas de todos los tipos. Sirve para «¿qué paradas tiene el tipo no programada?». Admite búsqueda por nombre o descripción, orden, paginación con per_page y page, y with_trashed para incluir las inactivas. Cada parada trae su ítem, su configuración (settings, donde puede venir la duración en minutos de las programadas) y el tipo al que pertenece. No lista los paros ocurridos en un turno; esos salen en los registros de trabajo. Solo consulta.';
}
