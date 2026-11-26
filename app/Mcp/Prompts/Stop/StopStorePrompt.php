<?php

namespace App\Mcp\Prompts\Stop;

use App\Mcp\Prompts\RoutePrompt;

class StopStorePrompt extends RoutePrompt
{
    protected string $name = 'stop_store';

    protected string $title = 'Crear una parada';

    protected string $description = 'Crear una parada';

    protected string $method = 'POST';

    protected string $path = '/typification/stop_types/stops/store';

    protected string $business = 'Crea un registro nuevo de stop. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar stop. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.

Notas: Crea la parada y la asocia al tipo de parada indicado.';
}
