<?php

namespace App\Mcp\Prompts\PensionFund;

use App\Mcp\Prompts\RoutePrompt;

class PensionFundUpdatePrompt extends RoutePrompt
{
    protected string $name = 'pension_fund_update';

    protected string $title = 'Editar un fondo de pensión';

    protected string $description = 'Editar un fondo de pensión';

    protected string $method = 'PUT';

    protected string $path = '/pension_funds/update/{id}';

    protected string $business = 'Modifica un registro existente de pension fund, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un pension fund. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.';
}
