<?php

namespace App\Mcp\Prompts\Area;

use App\Mcp\Prompts\RoutePrompt;

class AreaStorePrompt extends RoutePrompt
{
    protected string $name = 'area_store';

    protected string $title = 'Crear un área';

    protected string $description = 'Crear un área';

    protected string $method = 'POST';

    protected string $path = '/organizational_structure/areas/store';

    protected string $business = 'Crea un área de la estructura organizacional (por ejemplo un departamento de la empresa) en el catálogo de áreas. Se usa cuando falta un área para asignar cargos. Nace sin cargos asociados; estos se vinculan desde el recurso de cargos. Se RECHAZA si nombre o descripción no están en MAYÚSCULAS (uppercase rechaza, no convierte), si el nombre tiene menos de 4 caracteres o ya existe otra área con ese nombre (incluidas eliminadas). Confirma con la persona nombre y descripción exactos en mayúsculas. Es reversible: se edita o elimina después.';
}
