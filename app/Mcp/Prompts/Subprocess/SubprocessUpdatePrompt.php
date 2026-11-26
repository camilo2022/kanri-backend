<?php

namespace App\Mcp\Prompts\Subprocess;

use App\Mcp\Prompts\RoutePrompt;

class SubprocessUpdatePrompt extends RoutePrompt
{
    protected string $name = 'subprocess_update';

    protected string $title = 'Editar un subproceso';

    protected string $description = 'Editar un subproceso';

    protected string $method = 'PUT';

    protected string $path = '/workflow/processes/subprocesses/update/{id}';

    protected string $business = 'Edita un subproceso: nombre, descripción, siguientes y settings, con las mismas validaciones que al crearlo (nombre y color únicos; siguientes solo hermanos del proceso en secuencia, sin ser él mismo ni formar ciclos; un regreso debe apuntar a un paso anterior; ciclos revisados tono por tono). El schema no se edita aquí: se conserva. Efecto: reemplaza las flechas de siguientes solo si se envía after_subprocesses. Las claves de settings cambian reglas del flujo: in_technical_sheet decide si va solo si la ficha lo marca, only_after_previous si exige el anterior, next_conditions filtra por tono, productivity_measurement, time_multiplier, units_entry y schedule_units cambian cómo se mide, programa y cuenta. Responde 201 en vez de 200. La IA debe confirmar los cambios y advertir del impacto. Reversible editando.';
}
