<?php

namespace App\Mcp\Prompts\Size;

use App\Mcp\Prompts\RoutePrompt;

class SizeRestorePrompt extends RoutePrompt
{
    protected string $name = 'size_restore';

    protected string $title = 'Restaurar una talla desactivada';

    protected string $description = 'Restaurar una talla desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/sizes/restore/{id}';

    protected string $business = 'Reactiva una talla eliminada con su nombre, descripción y código originales. Se RECHAZA si la talla no existe o no está eliminada. No hay choque de nombre ni de código, porque la unicidad ya consideraba también a las eliminadas (el código se verifica en el campo de configuración). No cambia otros datos. Confirma con la persona cuál talla recuperar. Es reversible: se puede volver a eliminar.';
}
