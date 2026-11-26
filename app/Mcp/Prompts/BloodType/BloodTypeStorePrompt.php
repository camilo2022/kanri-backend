<?php

namespace App\Mcp\Prompts\BloodType;

use App\Mcp\Prompts\RoutePrompt;

class BloodTypeStorePrompt extends RoutePrompt
{
    protected string $name = 'blood_type_store';

    protected string $title = 'Crear un tipo de sangre';

    protected string $description = 'Crear un tipo de sangre';

    protected string $method = 'POST';

    protected string $path = '/typification/blood_types/store';

    protected string $business = 'Crea un registro nuevo de blood type. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar blood type. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
