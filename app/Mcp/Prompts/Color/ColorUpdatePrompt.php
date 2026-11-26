<?php

namespace App\Mcp\Prompts\Color;

use App\Mcp\Prompts\RoutePrompt;

class ColorUpdatePrompt extends RoutePrompt
{
    protected string $name = 'color_update';

    protected string $title = 'Editar un color';

    protected string $description = 'Editar un color';

    protected string $method = 'PUT';

    protected string $path = '/colors/update/{id}';

    protected string $business = 'Modifica un registro existente de color, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un color. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre, descripción y `settings` completo.';
}
