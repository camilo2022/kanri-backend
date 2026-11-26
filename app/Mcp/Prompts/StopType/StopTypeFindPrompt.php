<?php

namespace App\Mcp\Prompts\StopType;

use App\Mcp\Prompts\RoutePrompt;

class StopTypeFindPrompt extends RoutePrompt
{
    protected string $name = 'stop_type_find';

    protected string $title = 'Obtener un tipo de parada';

    protected string $description = 'Obtener un tipo de parada';

    protected string $method = 'GET';

    protected string $path = '/typification/stop_types/find/{id}';

    protected string $business = 'Consulta un tipo de parada por su id numérico: su nombre, su descripción y su configuración. Úsala cuando ya tengas el id, por ejemplo tomado del listado, con frases como «muéstrame el tipo de parada X». El id debe pertenecer al catálogo de tipos de parada; si no existe, responde error de validación. No trae las paradas que pertenecen al tipo; para eso está la consulta de paradas de un tipo. Solo consulta.';
}
