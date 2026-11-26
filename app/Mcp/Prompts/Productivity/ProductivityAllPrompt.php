<?php

namespace App\Mcp\Prompts\Productivity;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityAllPrompt extends RoutePrompt
{
    protected string $name = 'productivity_all';

    protected string $title = 'Listar los turnos de un proceso, subproceso u operación';

    protected string $description = 'Listar los turnos de un proceso, subproceso u operación';

    protected string $method = 'GET';

    protected string $path = '/productivity/all';

    protected string $business = 'Lista los turnos de un proceso, subproceso u operación. Un turno es la franja de trabajo de un día, con su hora de inicio y fin y su meta por hora, y su estado es Por iniciar, En curso o Terminado. Sirve para «¿qué turnos tiene Corte hoy?» o «muéstrame los turnos de la semana». Es obligatorio indicar model_type (proceso, subproceso u operación) y model_id del elemento. Se puede acotar a un día con date o a un rango con date_from y date_to (formato año-mes-día; date_to no puede ser anterior a date_from), además de ordenar y paginar con per_page y page; sin per_page devuelve todos. Cada turno trae el elemento y sus registros de trabajo con los traslados e ingresos asociados. No trae las opciones de programación ni el detalle completo de un turno; para eso están otras consultas. Solo lee.';
}
