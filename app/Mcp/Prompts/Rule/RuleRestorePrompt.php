<?php

namespace App\Mcp\Prompts\Rule;

use App\Mcp\Prompts\RoutePrompt;

class RuleRestorePrompt extends RoutePrompt
{
    protected string $name = 'rule_restore';

    protected string $title = 'Restaurar una regla específica';

    protected string $description = 'Restaurar una regla específica';

    protected string $method = 'PATCH';

    protected string $path = '/rules/restore/{id}';

    protected string $business = 'Reactiva una regla estándar de productividad eliminada, con sus capacidades y relaciones. Se RECHAZA si la regla no existe, si no está eliminada, o si ya existe otra regla activa con la misma combinación de relaciones (proceso, subproceso, operación y criterios); en ese caso hay que eliminar o editar la otra primero. Al activarse, el sistema vuelve a considerarla al calcular tiempos de producción. Confirma con la persona cuál regla recuperar y que no se solape con una vigente. Es reversible: se puede volver a eliminar.';
}
