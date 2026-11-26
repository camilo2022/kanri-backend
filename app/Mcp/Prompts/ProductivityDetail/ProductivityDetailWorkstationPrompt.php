<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailWorkstationPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_workstation';

    protected string $title = 'Resolver el QR de un puesto de trabajo';

    protected string $description = 'Resolver el QR de un puesto de trabajo';

    protected string $method = 'GET';

    protected string $path = '/productivity/details/workstation/{token}';

    protected string $business = 'Resuelve el código QR de un puesto de trabajo. El token es un uuid, el que está en settings.uuid del proceso, subproceso u operación; se trabaja solo en el nivel más bajo, así que un proceso con subprocesos o un subproceso con operaciones no sirve y responde error de validación, igual que un código inexistente. Devuelve el puesto con sus padres, los turnos de su proceso (de hoy en adelante y el del registro en curso) y el registro de trabajo en curso del puesto si lo hay. También lista los lotes que se pueden trabajar en el puesto: primero los programados y sin iniciar en el turno abierto, con su hora, y luego los demás. Úsala con «¿qué se puede trabajar en este puesto?». No inicia el trabajo ni decide con qué turno se trabaja.';
}
