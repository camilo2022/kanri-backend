<?php

namespace App\Mcp\Prompts\CompensationFund;

use App\Mcp\Prompts\RoutePrompt;

class CompensationFundAllPrompt extends RoutePrompt
{
    protected string $name = 'compensation_fund_all';

    protected string $title = 'Listar las cajas de compensación';

    protected string $description = 'Listar las cajas de compensación';

    protected string $method = 'GET';

    protected string $path = '/compensation_funds/all';

    protected string $business = 'Devuelve la lista de cajas de compensación registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar cajas de compensación, o cuando necesites el id de uno para otra acción.

Notas: En esta lista no viene `settings`.';
}
