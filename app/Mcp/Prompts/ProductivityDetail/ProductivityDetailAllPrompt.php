<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailAllPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_all';

    protected string $title = 'Registros de trabajo de un turno';

    protected string $description = 'Registros de trabajo de un turno';

    protected string $method = 'GET';

    protected string $path = '/productivity/details/all/{productivity_id}';

    protected string $business = 'Lista los registros de trabajo de un turno, ordenados por hora de inicio. Un registro de trabajo es el trabajo de un lote en un puesto de trabajo dentro del turno, hecho por un grupo de empleados; puede estar programado, en curso o terminado. Sirve para «¿qué se trabajó en el turno de hoy?» o «¿cuánto rindió este puesto?». El id del turno es entero. Cada registro trae el puesto, la referencia y el lote, la regla estándar, los empleados, el inicio oficial y la hora real de escaneo, el fin, los minutos (duración, paros, trabajado y estándar), la eficiencia, los paros y las unidades. Esas unidades son los ingresos que se crearon en Corte o lo sumado con el +1 en procesos por unidades; en puestos que no cuentan unidades vienen en null. Los registros en curso traen estado En curso con su inicio y sus paros. Solo consulta.';
}
