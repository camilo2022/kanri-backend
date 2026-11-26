<?php

namespace App\Mcp\Prompts\CompensationFund;

use App\Mcp\Prompts\RoutePrompt;

class CompensationFundStorePrompt extends RoutePrompt
{
    protected string $name = 'compensation_fund_store';

    protected string $title = 'Crear una caja de compensación';

    protected string $description = 'Crear una caja de compensación';

    protected string $method = 'POST';

    protected string $path = '/compensation_funds/store';

    protected string $business = 'Crea un registro nuevo de compensation fund. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar compensation fund. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
