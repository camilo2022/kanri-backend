<?php

namespace App\Mcp\Prompts\Subgroup;

use App\Mcp\Prompts\RoutePrompt;

class SubgroupStorePrompt extends RoutePrompt
{
    protected string $name = 'subgroup_store';

    protected string $title = 'Crear un subgrupo';

    protected string $description = 'Crear un subgrupo';

    protected string $method = 'POST';

    protected string $path = '/classification/subgroups/store';

    protected string $business = 'Crea un registro nuevo de subgroup. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar subgroup. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
