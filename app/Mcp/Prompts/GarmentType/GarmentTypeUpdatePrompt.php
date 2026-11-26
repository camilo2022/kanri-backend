<?php

namespace App\Mcp\Prompts\GarmentType;

use App\Mcp\Prompts\RoutePrompt;

class GarmentTypeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'garment_type_update';

    protected string $title = 'Editar un tipo de prenda';

    protected string $description = 'Editar un tipo de prenda';

    protected string $method = 'PUT';

    protected string $path = '/typification/garment_types/update/{id}';

    protected string $business = 'Modifica un registro existente de garment type, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un garment type. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre, descripción y `settings` completo.';
}
