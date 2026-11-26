<?php

namespace App\Mcp\Prompts\Employee;

use App\Mcp\Prompts\RoutePrompt;

class EmployeeDeletePrompt extends RoutePrompt
{
    protected string $name = 'employee_delete';

    protected string $title = 'Desactivar un empleado';

    protected string $description = 'Desactivar un empleado';

    protected string $method = 'DELETE';

    protected string $path = '/employees/delete/{id}';

    protected string $business = 'Desactiva a un empleado (eliminación lógica: el registro se conserva con fecha de eliminación y no se borra). Se usa cuando el empleado deja de estar vinculado. Se rechaza si el empleado no existe o ya está desactivado. En el código leído no hay verificación de que tenga un usuario de acceso, movimientos o trabajo asignado; no se pudo confirmar si otras pantallas dejan de mostrarlo, ni si el usuario asociado pierde el acceso (eso se hace en la desactivación de usuarios). Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar al empleado y pedir confirmación expresa. Es reversible con la restauración de empleados.';
}
