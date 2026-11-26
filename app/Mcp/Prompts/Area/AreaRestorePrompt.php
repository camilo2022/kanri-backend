<?php

namespace App\Mcp\Prompts\Area;

use App\Mcp\Prompts\RoutePrompt;

class AreaRestorePrompt extends RoutePrompt
{
    protected string $name = 'area_restore';

    protected string $title = 'Restaurar un área desactivada';

    protected string $description = 'Restaurar un área desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/organizational_structure/areas/restore/{id}';

    protected string $business = 'Reactiva un área eliminada con su nombre y descripción originales. Se RECHAZA si el área no existe o no está eliminada. No puede haber choque de nombre, ya que la unicidad también contaba a las eliminadas. No cambia otros datos; los vínculos con cargos se conservan como estaban. Confirma con la persona cuál área quiere recuperar. Es reversible: se puede volver a eliminar.';
}
