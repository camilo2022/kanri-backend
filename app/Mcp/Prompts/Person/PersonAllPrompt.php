<?php

namespace App\Mcp\Prompts\Person;

use App\Mcp\Prompts\RoutePrompt;

class PersonAllPrompt extends RoutePrompt
{
    protected string $name = 'person_all';

    protected string $title = 'Listar las personas';

    protected string $description = 'Listar las personas';

    protected string $method = 'GET';

    protected string $path = '/people/all';

    protected string $business = 'Lista las personas registradas, que son los datos personales de cualquiera que se relacione con la empresa: empleados y también contactos de proveedores. Un empleado siempre se apoya en una persona. Úsala cuando pidan buscar una persona por nombre o documento, o saber quién es empleado y quién no. Cada persona trae género, tipo de sangre, foto, país del teléfono y su empleado si lo tiene. Filtros propios: with_employee (verdadero solo quienes son empleados, falso solo quienes no) y with_trashed para incluir las inactivas. Devuelve documento, fecha de nacimiento, correo, dirección y teléfono: son datos sensibles, no repetirlos sin necesidad.';
}
