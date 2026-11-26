<?php

namespace App\Mcp\Prompts\HealthEntity;

use App\Mcp\Prompts\RoutePrompt;

class HealthEntityAllPrompt extends RoutePrompt
{
    protected string $name = 'health_entity_all';

    protected string $title = 'Listar las entidades de salud';

    protected string $description = 'Listar las entidades de salud';

    protected string $method = 'GET';

    protected string $path = '/health_entities/all';

    protected string $business = 'Devuelve la lista de entidades de salud registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar entidades de salud, o cuando necesites el id de uno para otra acción.

Notas: En esta lista no viene `settings`.';
}
