<?php

namespace App\Mcp\Prompts\Submodule;

use App\Mcp\Prompts\RoutePrompt;

class SubmoduleDeletePrompt extends RoutePrompt
{
    protected string $name = 'submodule_delete';

    protected string $title = 'Desactivar un submódulo';

    protected string $description = 'Desactivar un submódulo';

    protected string $method = 'DELETE';

    protected string $path = '/navegation/modules/submodules/delete/{id}';

    protected string $business = 'Elimina (borrado lógico) un submódulo, de modo que la entrada del menú deja de mostrarse. Se RECHAZA si el submódulo no existe o ya está eliminado. No se revisó ninguna dependencia adicional en el código. No elimina el permiso asociado ni el módulo. Mientras esté eliminado, su permiso y su url siguen contando como ocupados para la unicidad, de modo que no se pueden reutilizar en otro submódulo. Antes de ejecutarlo, confirma con la persona el submódulo exacto. Es reversible con la ruta de restauración.';
}
