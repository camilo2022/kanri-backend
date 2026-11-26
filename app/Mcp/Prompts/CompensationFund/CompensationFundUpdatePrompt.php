<?php

namespace App\Mcp\Prompts\CompensationFund;

use App\Mcp\Prompts\RoutePrompt;

class CompensationFundUpdatePrompt extends RoutePrompt
{
    protected string $name = 'compensation_fund_update';

    protected string $title = 'Editar una caja de compensación';

    protected string $description = 'Editar una caja de compensación';

    protected string $method = 'PUT';

    protected string $path = '/compensation_funds/update/{id}';

    protected string $business = 'Modifica un registro existente de compensation fund, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un compensation fund. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.';
}
