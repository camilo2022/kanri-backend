<?php

namespace App\Mcp\Prompts\WashTone;

use App\Mcp\Prompts\RoutePrompt;

class WashToneUpdatePrompt extends RoutePrompt
{
    protected string $name = 'wash_tone_update';

    protected string $title = 'Editar un tono de lavado';

    protected string $description = 'Editar un tono de lavado';

    protected string $method = 'PUT';

    protected string $path = '/wash_tones/update/{id}';

    protected string $business = 'Modifica un registro existente de wash tone, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un wash tone. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre, descripción y `settings` completo.';
}
