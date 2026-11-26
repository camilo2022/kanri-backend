<?php

namespace App\Mcp\Prompts\Person;

use App\Mcp\Prompts\RoutePrompt;

class PersonDeletePrompt extends RoutePrompt
{
    protected string $name = 'person_delete';

    protected string $title = 'Desactivar una persona';

    protected string $description = 'Desactivar una persona';

    protected string $method = 'DELETE';

    protected string $path = '/people/delete/{id}';

    protected string $business = 'Desactiva a una persona (eliminación lógica: se conserva con fecha de eliminación). Se rechaza si no existe o ya está desactivada. Las personas desactivadas dejan de poder elegirse al crear o editar empleados (esa validación exige persona activa), pero en el código leído no hay una verificación que impida desactivar a alguien que ya es empleado o contacto de un proveedor; no se pudo confirmar el efecto sobre esos registros. Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar a la persona, verificar si es empleado y pedir confirmación expresa. Es reversible con la restauración de personas.';
}
