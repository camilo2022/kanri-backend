<?php

namespace App\Mcp\Prompts\Area;

use App\Mcp\Prompts\RoutePrompt;

class AreaDeletePrompt extends RoutePrompt
{
    protected string $name = 'area_delete';

    protected string $title = 'Desactivar un área';

    protected string $description = 'Desactivar un área';

    protected string $method = 'DELETE';

    protected string $path = '/organizational_structure/areas/delete/{id}';

    protected string $business = 'Elimina (borrado lógico) un área de la estructura organizacional. Se RECHAZA si el área no existe o ya está eliminada. En el código revisado no se verifica si hay cargos asociados al área, así que no se puede asegurar que el sistema impida eliminarla con cargos vinculados. No borra los cargos ni sus vínculos. Antes de ejecutarlo, confirma con la persona el nombre del área. Es reversible con la ruta de restauración.';
}
