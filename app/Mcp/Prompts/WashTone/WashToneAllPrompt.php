<?php

namespace App\Mcp\Prompts\WashTone;

use App\Mcp\Prompts\RoutePrompt;

class WashToneAllPrompt extends RoutePrompt
{
    protected string $name = 'wash_tone_all';

    protected string $title = 'Listar los tonos de lavado';

    protected string $description = 'Listar los tonos de lavado';

    protected string $method = 'GET';

    protected string $path = '/wash_tones/all';

    protected string $business = 'Devuelve la lista de tonos de lavado registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar tonos de lavado, o cuando necesites el id de uno para otra acción.

Notas: También la pueden consultar quienes configuran procesos, subprocesos u operaciones.';
}
