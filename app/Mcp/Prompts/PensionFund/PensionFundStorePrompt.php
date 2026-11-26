<?php

namespace App\Mcp\Prompts\PensionFund;

use App\Mcp\Prompts\RoutePrompt;

class PensionFundStorePrompt extends RoutePrompt
{
    protected string $name = 'pension_fund_store';

    protected string $title = 'Crear un fondo de pensión';

    protected string $description = 'Crear un fondo de pensión';

    protected string $method = 'POST';

    protected string $path = '/pension_funds/store';

    protected string $business = 'Crea un registro nuevo de pension fund. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar pension fund. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
