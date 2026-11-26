<?php

namespace App\Mcp\Prompts\Employee;

use App\Mcp\Prompts\RoutePrompt;

class EmployeeStorePrompt extends RoutePrompt
{
    protected string $name = 'employee_store';

    protected string $title = 'Crear un empleado';

    protected string $description = 'Crear un empleado';

    protected string $method = 'POST';

    protected string $path = '/employees/store';

    protected string $business = 'Registra a una persona ya existente como empleado de la empresa. Se usa al contratar o vincular a alguien. Campos clave: «persona» (debe estar activa y no puede ser ya empleado: cada persona tiene un único registro de empleado); cargo, administradora de riesgos, entidad de salud, fondo de pensión y caja de compensación (catálogos activos); fecha de inicio y fecha de fin (opcional, debe ser posterior al inicio); y puesto de trabajo (opcional), que debe ser un proceso, subproceso u operación sin hijos: si el elegido tiene subprocesos u operaciones, se rechaza y hay que elegir uno de ellos. No crea el usuario de acceso; eso se hace después en la creación de usuarios. Queda en la auditoría. Antes de ejecutarlo, la IA debe confirmar la persona, el cargo y las fechas. Es reversible: se puede editar o eliminar (desactivar).';
}
