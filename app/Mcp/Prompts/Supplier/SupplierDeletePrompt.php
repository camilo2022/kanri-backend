<?php

namespace App\Mcp\Prompts\Supplier;

use App\Mcp\Prompts\RoutePrompt;

class SupplierDeletePrompt extends RoutePrompt
{
    protected string $name = 'supplier_delete';

    protected string $title = 'Desactivar un proveedor';

    protected string $description = 'Desactivar un proveedor';

    protected string $method = 'DELETE';

    protected string $path = '/typification/supplier_types/suppliers/delete/{id}';

    protected string $business = 'Desactiva un proveedor (eliminación lógica: se conserva con fecha de eliminación, junto con su persona de contacto y su cuenta). Se rechaza si no existe o ya está desactivado. En el código leído no hay verificación de compras, fichas u otros registros que lo usen; no se pudo confirmar el impacto. Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar el proveedor y pedir confirmación expresa. Es reversible con la restauración de proveedores.';
}
