<?php

namespace App\Mcp\Prompts\Stop;

use App\Mcp\Prompts\RoutePrompt;

class StopFindPrompt extends RoutePrompt
{
    protected string $name = 'stop_find';

    protected string $title = 'Obtener una parada';

    protected string $description = 'Obtener una parada';

    protected string $method = 'GET';

    protected string $path = '/typification/stop_types/stops/find/{id}';

    protected string $business = 'Consulta una parada del catálogo por su id numérico: nombre, descripción, configuración (settings) y el tipo de parada al que pertenece. Úsala cuando ya tengas el id de la parada, por ejemplo del listado, con frases como «muéstrame la parada ALMUERZO». El id debe pertenecer al catálogo de paradas; si no, responde error de validación. No muestra cuándo ocurrió esa parada en un turno, solo su definición. Solo consulta.';
}
