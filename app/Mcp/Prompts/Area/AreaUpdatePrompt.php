<?php

namespace App\Mcp\Prompts\Area;

use App\Mcp\Prompts\RoutePrompt;

class AreaUpdatePrompt extends RoutePrompt
{
    protected string $name = 'area_update';

    protected string $title = 'Editar un área';

    protected string $description = 'Editar un área';

    protected string $method = 'PUT';

    protected string $path = '/organizational_structure/areas/update/{id}';

    protected string $business = 'Edita el nombre y la descripción de un área. Los cargos ya vinculados al área se mantienen. Se RECHAZA si el área no existe, si nombre o descripción no vienen en MAYÚSCULAS (uppercase rechaza, no convierte), si el nombre tiene menos de 4 caracteres o coincide con el de otra área (activa o eliminada). La validación solo exige que el área exista, aunque esté eliminada. Confirma con la persona cuál área se cambia y los textos nuevos. Es reversible volviendo a editarla; el historial queda en la auditoría.';
}
