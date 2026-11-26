<?php

namespace App\Mcp\Prompts\Bank;

use App\Mcp\Prompts\RoutePrompt;

class BankStorePrompt extends RoutePrompt
{
    protected string $name = 'bank_store';

    protected string $title = 'Crear un banco';

    protected string $description = 'Crear un banco';

    protected string $method = 'POST';

    protected string $path = '/banks/store';

    protected string $business = 'Crea un banco nuevo en el catálogo de bancos. Se usa cuando falta una entidad financiera para registrar cuentas bancarias. El banco nace sin reglas de número de cuenta (settings vacío); estas se definen después en la ruta de configuración. Se RECHAZA si el nombre o la descripción no están en MAYÚSCULAS (la regla uppercase rechaza, no convierte), si el nombre tiene menos de 4 caracteres o si ya existe otro banco con ese nombre, incluidos los eliminados. Antes de ejecutarlo, confirma con la persona el nombre y la descripción exactos, ya en mayúsculas. Es reversible: se puede editar o eliminar después.';
}
