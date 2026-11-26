<?php

namespace App\Mcp\Prompts\Person;

use App\Mcp\Prompts\RoutePrompt;

class PersonRestorePrompt extends RoutePrompt
{
    protected string $name = 'person_restore';

    protected string $title = 'Restaurar una persona desactivada';

    protected string $description = 'Restaurar una persona desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/people/restore/{id}';

    protected string $business = 'Reactiva a una persona desactivada con los mismos datos que tenía. Se rechaza si la persona no existe o no está desactivada. No revalida su documento, teléfono ni ubicación contra las reglas actuales, ni toca a su empleado o a su usuario (si fueron desactivados aparte, siguen así). Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar a la persona y pedir confirmación. Es reversible: se puede volver a desactivar con la eliminación.';
}
