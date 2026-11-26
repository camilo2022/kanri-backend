<?php

namespace App\Mcp\Prompts\Bank;

use App\Mcp\Prompts\RoutePrompt;

class BankRestorePrompt extends RoutePrompt
{
    protected string $name = 'bank_restore';

    protected string $title = 'Restaurar un banco desactivado';

    protected string $description = 'Restaurar un banco desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/banks/restore/{id}';

    protected string $business = 'Reactiva un banco que fue eliminado, devolviéndolo al catálogo con su nombre, descripción y reglas de cuenta tal como estaban. Se RECHAZA si el banco no existe o no está eliminado (es decir, ya está activo). No hay conflicto posible de nombre, porque la unicidad ya contaba también a los eliminados. No cambia otros datos. Antes de ejecutarlo, confirma con la persona cuál banco eliminado quiere recuperar. Es reversible: se puede volver a eliminar.';
}
