<?php

namespace App\Mcp\Prompts\Submodule;

use App\Mcp\Prompts\RoutePrompt;

class SubmoduleRestorePrompt extends RoutePrompt
{
    protected string $name = 'submodule_restore';

    protected string $title = 'Restaurar un submódulo desactivado';

    protected string $description = 'Restaurar un submódulo desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/navegation/modules/submodules/restore/{id}';

    protected string $business = 'Reactiva un submódulo eliminado, con su módulo, permiso, url e icono originales, y la entrada vuelve al menú. Se RECHAZA si el submódulo no existe o no está eliminado. No hay conflicto de nombre, url ni permiso, porque la unicidad también consideraba a los eliminados. No se comprobó en el código si el módulo padre debe estar activo; no se pudo confirmar. Confirma con la persona cuál submódulo recuperar. Es reversible: se puede volver a eliminar.';
}
