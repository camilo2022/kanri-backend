<?php

namespace App\Mcp\Prompts\BackType;

use App\Mcp\Prompts\RoutePrompt;

class BackTypeStorePrompt extends RoutePrompt
{
    protected string $name = 'back_type_store';

    protected string $title = 'Crear un tipo de trasero';

    protected string $description = 'Crear un tipo de trasero';

    protected string $method = 'POST';

    protected string $path = '/typification/back_types/store';

    protected string $business = 'Crea un registro nuevo de back type. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar back type. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
