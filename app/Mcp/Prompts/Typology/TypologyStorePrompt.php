<?php

namespace App\Mcp\Prompts\Typology;

use App\Mcp\Prompts\RoutePrompt;

class TypologyStorePrompt extends RoutePrompt
{
    protected string $name = 'typology_store';

    protected string $title = 'Crear una tipología';

    protected string $description = 'Crear una tipología';

    protected string $method = 'POST';

    protected string $path = '/typification/typologies/store';

    protected string $business = 'Crea un registro nuevo de typology. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar typology. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.

Notas: Crea la tipología y la asocia a los procesos indicados.';
}
