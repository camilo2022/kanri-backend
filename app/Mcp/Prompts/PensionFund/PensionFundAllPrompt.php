<?php

namespace App\Mcp\Prompts\PensionFund;

use App\Mcp\Prompts\RoutePrompt;

class PensionFundAllPrompt extends RoutePrompt
{
    protected string $name = 'pension_fund_all';

    protected string $title = 'Listar los fondos de pensión';

    protected string $description = 'Listar los fondos de pensión';

    protected string $method = 'GET';

    protected string $path = '/pension_funds/all';

    protected string $business = 'Devuelve la lista de fondos de pensión registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar fondos de pensión, o cuando necesites el id de uno para otra acción.

Notas: En esta lista no vienen `item_id` ni `settings`.';
}
