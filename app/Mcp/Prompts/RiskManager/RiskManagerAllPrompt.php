<?php

namespace App\Mcp\Prompts\RiskManager;

use App\Mcp\Prompts\RoutePrompt;

class RiskManagerAllPrompt extends RoutePrompt
{
    protected string $name = 'risk_manager_all';

    protected string $title = 'Listar las administradoras de riesgos';

    protected string $description = 'Listar las administradoras de riesgos';

    protected string $method = 'GET';

    protected string $path = '/risk_managers/all';

    protected string $business = 'Devuelve la lista de administradoras de riesgos registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar administradoras de riesgos, o cuando necesites el id de uno para otra acción.

Notas: También la pueden consultar quienes crean o editan empleados (`employees.store`, `employees.update`). En esta lista no viene `settings`.';
}
