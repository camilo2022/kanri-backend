<?php

namespace App\Mcp\Prompts\Supplier;

use App\Mcp\Prompts\RoutePrompt;

class SupplierStorePrompt extends RoutePrompt
{
    protected string $name = 'supplier_store';

    protected string $title = 'Crear un proveedor';

    protected string $description = 'Crear un proveedor';

    protected string $method = 'POST';

    protected string $path = '/typification/supplier_types/suppliers/store';

    protected string $business = 'Crea un proveedor de un tipo de proveedor. Lo que se pide depende de la configuración del tipo: si el tipo exige persona de contacto, se piden y se crean sus datos como una persona nueva (mismas reglas que en personas: documento según su tipo, teléfono según su país, ubicación al último nivel); si exige cuenta bancaria, se piden banco, tipo y número de cuenta y un certificado bancario en PDF. Se rechaza si el nombre o el código (de 1 a 5 dígitos) ya existen, si el número de cuenta no cumple la regla de su banco y tipo de cuenta, o si el nombre del certificado no coincide con el del proveedor (se comparan sin tildes, espacios ni signos). Queda en la auditoría. La IA debe confirmar nombre, código, tipo y datos de contacto y bancarios. Es reversible: se puede editar o desactivar.';
}
