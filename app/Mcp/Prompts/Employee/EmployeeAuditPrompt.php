<?php

namespace App\Mcp\Prompts\Employee;

use App\Mcp\Prompts\RoutePrompt;

class EmployeeAuditPrompt extends RoutePrompt
{
    protected string $name = 'employee_audit';

    protected string $title = 'Historial de cambios de un empleado';

    protected string $description = 'Historial de cambios de un empleado';

    protected string $method = 'GET';

    protected string $path = '/employees/audit/{id}';

    protected string $business = 'Muestra la línea de tiempo de un registro de empleado: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.';
}
