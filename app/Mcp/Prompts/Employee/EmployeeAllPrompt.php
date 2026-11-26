<?php

namespace App\Mcp\Prompts\Employee;

use App\Mcp\Prompts\RoutePrompt;

class EmployeeAllPrompt extends RoutePrompt
{
    protected string $name = 'employee_all';

    protected string $title = 'Listar los empleados';

    protected string $description = 'Listar los empleados';

    protected string $method = 'GET';

    protected string $path = '/employees/all';

    protected string $business = 'Lista los empleados de la empresa. Un empleado pertenece a una persona (sus datos personales), tiene un cargo que cuelga de un área, y puede tener un usuario para entrar al sistema. Úsala cuando pidan ver, buscar o contar empleados, o saber quién ocupa cierto cargo o quién no tiene usuario. Cada empleado trae su persona con foto, cargo con área, usuario con roles y permisos (incluso si el usuario está inactivo), ARL, EPS, fondo de pensión, caja de compensación y puesto de trabajo. Filtros propios: position_id para un cargo, with_user para quienes sí o no tienen usuario, y with_trashed para incluir empleados inactivos (retirados). La persona incluida trae documento, correo y teléfono: no repetirlos sin necesidad.';
}
