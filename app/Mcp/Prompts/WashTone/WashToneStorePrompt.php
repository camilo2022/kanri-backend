<?php

namespace App\Mcp\Prompts\WashTone;

use App\Mcp\Prompts\RoutePrompt;

class WashToneStorePrompt extends RoutePrompt
{
    protected string $name = 'wash_tone_store';

    protected string $title = 'Crear un tono de lavado';

    protected string $description = 'Crear un tono de lavado';

    protected string $method = 'POST';

    protected string $path = '/wash_tones/store';

    protected string $business = 'Crea un registro nuevo de wash tone. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar wash tone. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
