<?php

namespace App\Mcp\Prompts\Module;

use App\Mcp\Prompts\RoutePrompt;

class ModuleDeletePrompt extends RoutePrompt
{
    protected string $name = 'module_delete';

    protected string $title = 'Desactivar un módulo';

    protected string $description = 'Desactivar un módulo';

    protected string $method = 'DELETE';

    protected string $path = '/navegation/modules/delete/{id}';

    protected string $business = 'Elimina (borrado lógico) un módulo del menú de navegación. Se RECHAZA si el módulo no existe o ya está eliminado. En el código revisado no hay validación que impida eliminar un módulo que aún tiene submódulos, ni se eliminan sus submódulos en cascada; no se pudo confirmar cómo se muestran en el menú los submódulos de un módulo eliminado. Antes de ejecutarlo, confirma con la persona el módulo y advierte que puede afectar el menú de los usuarios. Es reversible con la ruta de restauración.';
}
