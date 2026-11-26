<?php

namespace App\Mcp\Prompts\Employee;

use App\Mcp\Prompts\RoutePrompt;

class EmployeeRestorePrompt extends RoutePrompt
{
    protected string $name = 'employee_restore';

    protected string $title = 'Restaurar un empleado desactivado';

    protected string $description = 'Restaurar un empleado desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/employees/restore/{id}';

    protected string $business = 'Reactiva a un empleado que estaba desactivado, devolviéndolo con los mismos datos que tenía. Se rechaza si el empleado no existe o si no está desactivado. No modifica su persona, cargo ni su usuario de acceso (si el usuario fue desactivado aparte, sigue desactivado). En el código leído no se verifica que la persona, el cargo o las entidades sigan activos al restaurar; no se pudo confirmar qué ocurre si alguno fue desactivado entretanto. Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar al empleado y confirmar. Es reversible: se puede volver a desactivar con la eliminación.';
}
