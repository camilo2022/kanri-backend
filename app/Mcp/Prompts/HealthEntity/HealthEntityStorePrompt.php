<?php

namespace App\Mcp\Prompts\HealthEntity;

use App\Mcp\Prompts\RoutePrompt;

class HealthEntityStorePrompt extends RoutePrompt
{
    protected string $name = 'health_entity_store';

    protected string $title = 'Crear una entidad de salud';

    protected string $description = 'Crear una entidad de salud';

    protected string $method = 'POST';

    protected string $path = '/health_entities/store';

    protected string $business = 'Crea un registro nuevo de health entity. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar health entity. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
