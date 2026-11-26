<?php

namespace App\Mcp\Prompts\BloodType;

use App\Mcp\Prompts\RoutePrompt;

class BloodTypeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'blood_type_update';

    protected string $title = 'Editar un tipo de sangre';

    protected string $description = 'Editar un tipo de sangre';

    protected string $method = 'PUT';

    protected string $path = '/typification/blood_types/update/{id}';

    protected string $business = 'Modifica un registro existente de blood type, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un blood type. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.';
}
