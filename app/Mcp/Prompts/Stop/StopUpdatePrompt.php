<?php

namespace App\Mcp\Prompts\Stop;

use App\Mcp\Prompts\RoutePrompt;

class StopUpdatePrompt extends RoutePrompt
{
    protected string $name = 'stop_update';

    protected string $title = 'Editar una parada';

    protected string $description = 'Editar una parada';

    protected string $method = 'PUT';

    protected string $path = '/typification/stop_types/stops/update/{id}';

    protected string $business = 'Modifica un registro existente de stop, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un stop. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre, descripción y `settings`; no cambia el tipo de parada. Si no se envía `settings`, queda en `null`.';
}
