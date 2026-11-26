<?php

namespace App\Mcp\Prompts\Typology;

use App\Mcp\Prompts\RoutePrompt;

class TypologyUpdatePrompt extends RoutePrompt
{
    protected string $name = 'typology_update';

    protected string $title = 'Editar una tipología';

    protected string $description = 'Editar una tipología';

    protected string $method = 'PUT';

    protected string $path = '/typification/typologies/update/{id}';

    protected string $business = 'Modifica un registro existente de typology, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un typology. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre, descripción, `settings` y la lista de procesos.';
}
