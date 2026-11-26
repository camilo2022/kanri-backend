<?php

namespace App\Mcp\Prompts\WaistbandType;

use App\Mcp\Prompts\RoutePrompt;

class WaistbandTypeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'waistband_type_update';

    protected string $title = 'Editar un tipo de pretina';

    protected string $description = 'Editar un tipo de pretina';

    protected string $method = 'PUT';

    protected string $path = '/typification/waistband_types/update/{id}';

    protected string $business = 'Modifica un registro existente de waistband type, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un waistband type. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre, descripción y `settings` completo.';
}
