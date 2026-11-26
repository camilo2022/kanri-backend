<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailScheduleStorePrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_schedule_store';

    protected string $title = 'Programar en cadena un turno de Corte';

    protected string $description = 'Programar en cadena un turno de Corte';

    protected string $method = 'POST';

    protected string $path = '/productivity/details/schedule/{productivity_id}';

    protected string $business = 'Programa en cadena un turno por lote (por ejemplo Corte): se envían las referencias en el orden de trabajo, con los puestos que el líder quitó (removed) y los empleados por puesto si los cambió; el sistema calcula las horas. Con preview verdadero solo calcula y muestra la tabla, sin guardar. Se rechaza si el proceso no se programa en cadena, si el turno terminó, y por referencia si su orden está cancelada, no es de la ruta del lugar de producción, la ficha del proceso sigue Pendiente o no tiene unidades en las tallas activas de la marca; la cadena puede devolver además problemas de cruce o capacidad. Efecto (sin preview): borra la programación sin iniciar del turno y la recrea con filas, empleados y horas calculadas; lo ya iniciado no se toca. La IA debe confirmar orden de las referencias y advertir que reemplaza la programación previa. Reversible solo reprogramando.';
}
