<?php

namespace App\Mcp\Prompts\Supplier;

use App\Mcp\Prompts\RoutePrompt;

class SupplierRestorePrompt extends RoutePrompt
{
    protected string $name = 'supplier_restore';

    protected string $title = 'Restaurar un proveedor desactivado';

    protected string $description = 'Restaurar un proveedor desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/typification/supplier_types/suppliers/restore/{id}';

    protected string $business = 'Reactiva un proveedor desactivado con todos sus datos. Se rechaza si el proveedor no existe o no está desactivado. No revalida su código, cuenta bancaria ni contacto contra las reglas vigentes. Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar el proveedor y pedir confirmación. Es reversible: se puede volver a desactivar.';
}
