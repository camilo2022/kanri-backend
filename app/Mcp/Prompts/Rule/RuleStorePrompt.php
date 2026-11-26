<?php

namespace App\Mcp\Prompts\Rule;

use App\Mcp\Prompts\RoutePrompt;

class RuleStorePrompt extends RoutePrompt
{
    protected string $name = 'rule_store';

    protected string $title = 'Crear una regla';

    protected string $description = 'Crear una regla';

    protected string $method = 'POST';

    protected string $path = '/rules/store';

    protected string $business = 'Crea una regla estándar de productividad: define el tiempo que toma hacer algo según la cantidad de personas (capacidades), para un proceso y, opcionalmente, un subproceso u operación y otros criterios. Se usa para calcular tiempos y cuotas de trabajo. Se RECHAZA si el nombre no está en MAYÚSCULAS (uppercase rechaza), si ya existe, si no se indica el proceso, si el subproceso no pertenece al proceso o la operación al subproceso, si un criterio extra no está habilitado en la configuración de la regla o no existe, o si ya hay una regla activa con exactamente la misma combinación de relaciones. Si el puesto no se mide por lotes solo se permite una capacidad; si se mide por lotes, más personas deben implicar menos tiempo. Los criterios se enlazan a la regla. La descripción no se valida (no se pudo confirmar su uso). Confirma el alcance y las capacidades. Es reversible.';
}
