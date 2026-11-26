<?php

namespace App\Mcp\Prompts\PersonType;

use App\Mcp\Prompts\RoutePrompt;

class PersonTypeStorePrompt extends RoutePrompt
{
    protected string $name = 'person_type_store';

    protected string $title = 'Crear un tipo de persona';

    protected string $description = 'Crear un tipo de persona';

    protected string $method = 'POST';

    protected string $path = '/typification/person_types/store';

    protected string $business = 'Crea un registro nuevo de person type. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar person type. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
