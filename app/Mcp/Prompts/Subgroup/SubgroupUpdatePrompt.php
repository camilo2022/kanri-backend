<?php

namespace App\Mcp\Prompts\Subgroup;

use App\Mcp\Prompts\RoutePrompt;

class SubgroupUpdatePrompt extends RoutePrompt
{
    protected string $name = 'subgroup_update';

    protected string $title = 'Editar un subgrupo';

    protected string $description = 'Editar un subgrupo';

    protected string $method = 'PUT';

    protected string $path = '/classification/subgroups/update/{id}';

    protected string $business = 'Modifica un registro existente de subgroup, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un subgroup. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre y descripción.';
}
