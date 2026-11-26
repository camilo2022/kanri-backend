<?php

namespace App\Mcp\Prompts\Group;

use App\Mcp\Prompts\RoutePrompt;

class GroupStorePrompt extends RoutePrompt
{
    protected string $name = 'group_store';

    protected string $title = 'Crear un grupo';

    protected string $description = 'Crear un grupo';

    protected string $method = 'POST';

    protected string $path = '/classification/groups/store';

    protected string $business = 'Crea un registro nuevo de group. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar group. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
