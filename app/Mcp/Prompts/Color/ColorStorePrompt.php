<?php

namespace App\Mcp\Prompts\Color;

use App\Mcp\Prompts\RoutePrompt;

class ColorStorePrompt extends RoutePrompt
{
    protected string $name = 'color_store';

    protected string $title = 'Crear un color';

    protected string $description = 'Crear un color';

    protected string $method = 'POST';

    protected string $path = '/colors/store';

    protected string $business = 'Crea un registro nuevo de color. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar color. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
