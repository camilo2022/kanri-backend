<?php

namespace App\Mcp\Prompts\Bank;

use App\Mcp\Prompts\RoutePrompt;

class BankDeletePrompt extends RoutePrompt
{
    protected string $name = 'bank_delete';

    protected string $title = 'Desactivar un banco';

    protected string $description = 'Desactivar un banco';

    protected string $method = 'DELETE';

    protected string $path = '/banks/delete/{id}';

    protected string $business = 'Elimina (borrado lógico) un banco del catálogo, de modo que deja de aparecer entre los activos. Se RECHAZA si el banco no existe o ya está eliminado. En el código revisado no hay verificación de dependencias: no se comprobó si el banco tiene cuentas bancarias asociadas antes de eliminarlo, así que no se puede asegurar que el sistema lo impida. No cambia otros datos. Antes de ejecutarlo, confirma con la persona el nombre del banco que se eliminará. Es reversible con la ruta de restauración, que conserva nombre y reglas de cuenta.';
}
