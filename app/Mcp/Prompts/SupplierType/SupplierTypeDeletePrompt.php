<?php

namespace App\Mcp\Prompts\SupplierType;

use App\Mcp\Prompts\RoutePrompt;

class SupplierTypeDeletePrompt extends RoutePrompt
{
    protected string $name = 'supplier_type_delete';

    protected string $title = 'Desactivar un tipo de proveedor';

    protected string $description = 'Desactivar un tipo de proveedor';

    protected string $method = 'DELETE';

    protected string $path = '/typification/supplier_types/delete/{id}';

    protected string $business = 'Elimina un registro de supplier type, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un supplier type. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
