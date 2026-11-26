<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailWorkstationMinePrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_workstation_mine';

    protected string $title = 'Puestos de trabajo del empleado que inició sesión';

    protected string $description = 'Puestos de trabajo del empleado que inició sesión';

    protected string $method = 'GET';

    protected string $path = '/productivity/details/workstation/mine';

    protected string $business = 'Indica a qué puesto de trabajo debe ir el empleado que tiene la sesión iniciada, sin escanear un QR. Toma el empleado del usuario; si el usuario no tiene empleado, devuelve la lista vacía. Responde por orden de prioridad: primero sus trabajos en curso; si no hay, lo programado para ahora en el turno de hoy (o, si no hay nada para ahora, lo atrasado sin iniciar o lo próximo del día) con la hora programada; y si nada está programado, su puesto fijo, expandido a sus operaciones o subprocesos. Puede traer más de un puesto si su grupo tiene dos a la misma hora. Cada puesto trae su token, que es el uuid guardado en settings.uuid del puesto y sirve para consultar el puesto concreto. Úsala con «¿dónde me toca trabajar hoy?». No recibe parámetros y no inicia trabajo.';
}
