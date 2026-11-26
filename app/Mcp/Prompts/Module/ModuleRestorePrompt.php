<?php

namespace App\Mcp\Prompts\Module;

use App\Mcp\Prompts\RoutePrompt;

class ModuleRestorePrompt extends RoutePrompt
{
    protected string $name = 'module_restore';

    protected string $title = 'Restaurar un módulo desactivado';

    protected string $description = 'Restaurar un módulo desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/navegation/modules/restore/{id}';

    protected string $business = 'Reactiva un módulo del menú que estaba eliminado, con su nombre e icono originales. Se RECHAZA si el módulo no existe o no está eliminado. No puede chocar por nombre con otro módulo, porque la unicidad también considera a los eliminados. No cambia otros datos; los submódulos conservan su propio estado (no se restauran automáticamente). Confirma con la persona cuál módulo recuperar. Es reversible: se puede volver a eliminar.';
}
