<?php

namespace App\Mcp\Prompts\Gender;

use App\Mcp\Prompts\RoutePrompt;

class GenderStorePrompt extends RoutePrompt
{
    protected string $name = 'gender_store';

    protected string $title = 'Crear un género';

    protected string $description = 'Crear un género';

    protected string $method = 'POST';

    protected string $path = '/genders/store';

    protected string $business = 'Crea un registro nuevo de gender. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar gender. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
