<?php

namespace App\Mcp\Prompts\Rule;

use App\Mcp\Prompts\RoutePrompt;

class RuleUpdatePrompt extends RoutePrompt
{
    protected string $name = 'rule_update';

    protected string $title = 'Editar una regla específica';

    protected string $description = 'Editar una regla específica';

    protected string $method = 'PUT';

    protected string $path = '/rules/update/{id}';

    protected string $business = 'Edita una regla estándar de productividad: nombre, capacidades (personas y tiempo) y las relaciones que definen a qué proceso, subproceso, operación u otros criterios aplica. Las relaciones se sincronizan completas: las que no se envíen se eliminan de la regla. Se RECHAZA si el nombre no está en MAYÚSCULAS (uppercase rechaza) o ya lo usa otra regla, si falta el proceso, si el subproceso o la operación no son coherentes con su padre, si un criterio no está habilitado o no existe, si la combinación resultante ya la tiene otra regla activa, o si las capacidades incumplen la medición (una sola capacidad salvo medición por lotes, donde más personas exigen menos tiempo). Confirma con la persona los cambios, pues alteran los tiempos calculados. Es reversible editando de nuevo.';
}
