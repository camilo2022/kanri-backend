<?php

namespace App\Mcp\Prompts\Department;

use App\Mcp\Prompts\RoutePrompt;

class DepartmentAllPrompt extends RoutePrompt
{
    protected string $name = 'department_all';

    protected string $title = 'Listar los departamentos de un país';

    protected string $description = 'Listar los departamentos de un país';

    protected string $method = 'GET';

    protected string $path = '/locations/departments/all/{country_id}';

    protected string $business = 'Lista los departamentos (o estados) de un país; el id del país va en la ruta. La ubicación de una persona en Kanri baja de país a departamento y a ciudad. Úsala cuando pregunten qué departamentos tiene un país o cuántas ciudades tiene cada uno. Cada departamento trae nombre, tipo y la cantidad de ciudades que contiene. Busca por nombre y ordena alfabéticamente por defecto. No hay borrado lógico.';
}
