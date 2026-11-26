<?php

namespace App\Mcp\Prompts\Productivity;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityUpdatePrompt extends RoutePrompt
{
    protected string $name = 'productivity_update';

    protected string $title = 'Editar un turno';

    protected string $description = 'Editar un turno';

    protected string $method = 'PUT';

    protected string $path = '/productivity/update/{id}';

    protected string $business = 'Edita un turno: fecha, horario y meta por hora; el proceso del turno nunca cambia. Se rechaza si el turno está desactivado o Terminado. Si está En curso solo se puede cambiar la hora de fin (el resto se toma del turno guardado) y debe ser posterior a la hora actual. Si está Por iniciar se puede cambiar todo, pero no mover a fechas pasadas, ni cruzarse con otro turno del proceso, ni dejar la meta vacía cuando el proceso se mide por lotes. Efecto: si cambia el inicio o el día, toda la programación sin iniciar del turno se corre la misma cantidad de segundos (inicio y fin programados); lo ya iniciado no se toca. La IA debe confirmar el nuevo horario y avisar del corrimiento. Reversible editando de nuevo.';
}
