<?php

namespace App\Mcp\Prompts\Bank;

use App\Mcp\Prompts\RoutePrompt;

class BankAllPrompt extends RoutePrompt
{
    protected string $name = 'bank_all';

    protected string $title = 'Listar los bancos';

    protected string $description = 'Listar los bancos';

    protected string $method = 'GET';

    protected string $path = '/banks/all';

    protected string $business = 'Devuelve la lista de bancos registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar bancos, o cuando necesites el id de uno para otra acción.';
}
