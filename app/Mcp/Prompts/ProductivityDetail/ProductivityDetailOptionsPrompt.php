<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailOptionsPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_options';

    protected string $title = 'Opciones para programar un turno';

    protected string $description = 'Opciones para programar un turno';

    protected string $method = 'GET';

    protected string $path = '/productivity/details/options/{productivity_id}';

    protected string $business = 'Entrega los datos que necesita la pantalla de programación de un turno, es decir, asignar lotes a puestos y empleados antes del turno. Solo aplica a un turno de un proceso con puestos que tengan medición (por lote o por unidades) y que aún no haya terminado; en otro caso responde error de validación. Trae los trabajos de otros turnos que se cruzan en el tiempo (donde esos empleados ya están ocupados), si el proceso se programa por lote (el primer proceso) y si se programa como cadena, caso en que se programan las referencias y su orden y el sistema calcula las horas. Solo en el caso de cadena trae los puestos en el orden de la cadena con sus empleados, y también los grupos de puestos que se pueden trabajar a la vez. Los lotes y su estimado salen de la consulta de órdenes de producción. El id del turno es entero. Solo lee, no programa nada.';
}
