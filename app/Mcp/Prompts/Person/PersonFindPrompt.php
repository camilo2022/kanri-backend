<?php

namespace App\Mcp\Prompts\Person;

use App\Mcp\Prompts\RoutePrompt;

class PersonFindPrompt extends RoutePrompt
{
    protected string $name = 'person_find';

    protected string $title = 'Obtener una persona';

    protected string $description = 'Obtener una persona';

    protected string $method = 'GET';

    protected string $path = '/people/find/{id}';

    protected string $business = 'Muestra una persona con todo su detalle. Úsala cuando la persona pida los datos de alguien puntual y ya se conoce su id. Trae género, tipo de sangre, foto, tipo de documento con su tipo de persona, país del teléfono, su empleado si lo es, y la ubicación, que se guarda en las tablas propias de ciudad o departamento y llega con su departamento y país. Incluye documento, fecha de nacimiento, correo, teléfono y dirección: son datos sensibles, no repetirlos sin necesidad.';
}
