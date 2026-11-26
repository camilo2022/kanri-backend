<?php

namespace App\Mcp\Prompts\Productivity;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityFindPrompt extends RoutePrompt
{
    protected string $name = 'productivity_find';

    protected string $title = 'Consultar un turno activo';

    protected string $description = 'Consultar un turno activo';

    protected string $method = 'GET';

    protected string $path = '/productivity/find/{id}';

    protected string $business = 'Consulta un turno por su id numérico: la fecha, la hora de inicio y fin, la duración en horas, la meta por hora y por día, y su estado (Por iniciar, En curso o Terminado). Trae el proceso con sus subprocesos, operaciones y empleados, y los registros de trabajo del turno con la orden de producción y la ficha técnica, la regla estándar aplicada, los empleados, los paros y los traslados. También devuelve los lotes planeados del turno. Úsala con «muéstrame el turno 25» o «¿qué se programó en este turno?». No sirve para buscar turnos por fecha o proceso: para eso está el listado. Un turno eliminado no se encuentra. Solo consulta, no inicia ni cierra el turno.';
}
