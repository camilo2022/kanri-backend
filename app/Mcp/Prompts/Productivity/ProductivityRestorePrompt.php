<?php

namespace App\Mcp\Prompts\Productivity;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityRestorePrompt extends RoutePrompt
{
    protected string $name = 'productivity_restore';

    protected string $title = 'Activar un turno desactivado';

    protected string $description = 'Activar un turno desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/productivity/restore/{id}';

    protected string $business = 'Reactiva un turno que había sido desactivado. Se rechaza si el turno no existe o no está desactivado, o si su horario se cruza con el de otro turno activo del mismo proceso el mismo día (hay que ajustar el otro primero). No revisa si la fecha ya pasó, por lo que puede reactivar turnos antiguos; no se pudo confirmar si eso es intencional. Efecto: el turno vuelve a estar activo y recupera su estado calculado por el reloj; sus registros de trabajo no se tocan. La IA debe confirmar cuál turno reactivar (proceso, fecha, horario). Es reversible: se puede volver a desactivar mientras esté Por iniciar y sin registros.';
}
