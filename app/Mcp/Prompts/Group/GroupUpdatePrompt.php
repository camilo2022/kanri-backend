<?php

namespace App\Mcp\Prompts\Group;

use App\Mcp\Prompts\RoutePrompt;

class GroupUpdatePrompt extends RoutePrompt
{
    protected string $name = 'group_update';

    protected string $title = 'Editar un grupo';

    protected string $description = 'Editar un grupo';

    protected string $method = 'PUT';

    protected string $path = '/classification/groups/update/{id}';

    protected string $business = 'Modifica un registro existente de group, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un group. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.';
}
