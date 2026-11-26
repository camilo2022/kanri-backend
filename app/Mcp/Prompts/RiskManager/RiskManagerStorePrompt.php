<?php

namespace App\Mcp\Prompts\RiskManager;

use App\Mcp\Prompts\RoutePrompt;

class RiskManagerStorePrompt extends RoutePrompt
{
    protected string $name = 'risk_manager_store';

    protected string $title = 'Crear una administradora de riesgos';

    protected string $description = 'Crear una administradora de riesgos';

    protected string $method = 'POST';

    protected string $path = '/risk_managers/store';

    protected string $business = 'Crea un registro nuevo de risk manager. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar risk manager. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
