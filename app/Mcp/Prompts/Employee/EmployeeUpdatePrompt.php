<?php

namespace App\Mcp\Prompts\Employee;

use App\Mcp\Prompts\RoutePrompt;

class EmployeeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'employee_update';

    protected string $title = 'Editar un empleado';

    protected string $description = 'Editar un empleado';

    protected string $method = 'PUT';

    protected string $path = '/employees/update/{id}';

    protected string $business = 'Modifica los datos laborales de un empleado: persona, cargo, entidades de seguridad social, fechas y puesto de trabajo. Todos los campos se reemplazan con lo enviado, así que se debe enviar el registro completo. Se rechaza si la persona ya pertenece a otro empleado, si el cargo o las entidades no son activos de su catálogo, si la fecha de fin no es posterior a la de inicio o si el puesto es un proceso o subproceso que tiene hijos. Cambiar el cargo no actualiza los roles ni los permisos del usuario de acceso ya creado (esos se copiaron al crear el usuario); no se encontró código que los sincronice. Queda en la auditoría. La IA debe mostrar qué cambia y confirmar. Es reversible volviendo a editar.';
}
