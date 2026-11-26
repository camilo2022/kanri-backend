<?php

namespace App\Mcp\Prompts\RiskManager;

use App\Mcp\Prompts\RoutePrompt;

class RiskManagerUpdatePrompt extends RoutePrompt
{
    protected string $name = 'risk_manager_update';

    protected string $title = 'Editar una administradora de riesgos';

    protected string $description = 'Editar una administradora de riesgos';

    protected string $method = 'PUT';

    protected string $path = '/risk_managers/update/{id}';

    protected string $business = 'Modifica un registro existente de risk manager, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un risk manager. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre y descripción.';
}
