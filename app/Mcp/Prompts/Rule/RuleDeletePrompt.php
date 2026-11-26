<?php

namespace App\Mcp\Prompts\Rule;

use App\Mcp\Prompts\RoutePrompt;

class RuleDeletePrompt extends RoutePrompt
{
    protected string $name = 'rule_delete';

    protected string $title = 'Desactivar una regla específica';

    protected string $description = 'Desactivar una regla específica';

    protected string $method = 'DELETE';

    protected string $path = '/rules/delete/{id}';

    protected string $business = 'Elimina (borrado lógico) una regla estándar de productividad. Se RECHAZA si la regla no existe o ya está eliminada. En el código revisado no hay comprobación de dependencias. Al quedar eliminada, deja de contar como activa, por lo que libera su combinación de relaciones para crear otra regla igual; el sistema ya no la usará para calcular tiempos. Antes de ejecutarlo, confirma con la persona la regla y su alcance (proceso, subproceso, operación). Es reversible con la restauración, que falla si entretanto se creó otra regla activa con la misma combinación.';
}
