<?php

namespace App\Mcp\Prompts\Employee;

use App\Mcp\Prompts\RoutePrompt;

class EmployeeFindPrompt extends RoutePrompt
{
    protected string $name = 'employee_find';

    protected string $title = 'Obtener un empleado';

    protected string $description = 'Obtener un empleado';

    protected string $method = 'GET';

    protected string $path = '/employees/find/{id}';

    protected string $business = 'Muestra un empleado concreto con todo su detalle laboral y personal. Úsala cuando pregunten por un empleado puntual: su cargo, su área, su fecha de ingreso o retiro, su seguridad social o su usuario. Trae la persona completa (género, tipo de sangre, tipo de documento, país del teléfono y ubicación con ciudad, departamento y país), el cargo con su área, el usuario con roles y permisos, ARL, EPS, fondo de pensión, caja de compensación y puesto de trabajo. Incluye datos sensibles como documento, correo, teléfono y dirección: no repetirlos sin necesidad. Solo encuentra empleados activos; uno inactivo responde como no encontrado, y un id inexistente da error de validación.';
}
