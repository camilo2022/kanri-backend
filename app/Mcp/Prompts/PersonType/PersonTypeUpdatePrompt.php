<?php

namespace App\Mcp\Prompts\PersonType;

use App\Mcp\Prompts\RoutePrompt;

class PersonTypeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'person_type_update';

    protected string $title = 'Editar un tipo de persona';

    protected string $description = 'Editar un tipo de persona';

    protected string $method = 'PUT';

    protected string $path = '/typification/person_types/update/{id}';

    protected string $business = 'Modifica un registro existente de person type, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un person type. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.';
}
