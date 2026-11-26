<?php

namespace App\Mcp\Prompts\Supplier;

use App\Mcp\Prompts\RoutePrompt;

class SupplierUpdatePrompt extends RoutePrompt
{
    protected string $name = 'supplier_update';

    protected string $title = 'Editar un proveedor';

    protected string $description = 'Editar un proveedor';

    protected string $method = 'PUT';

    protected string $path = '/typification/supplier_types/suppliers/update/{id}';

    protected string $business = 'Edita un proveedor: nombre, descripción, código y, según su tipo, los datos de la persona de contacto y de la cuenta bancaria. El tipo de proveedor no cambia al editar (se usa solo para decidir qué datos aplican). Si el proveedor no tenía persona o cuenta, se crean. Se rechaza si el nombre o el código ya los usa otro proveedor, si el documento o correo de la persona pertenecen a otra, si la cuenta cambió y no cumple la regla de su banco, o si no hay certificado bancario ni previo ni nuevo. Un certificado nuevo se agrega a la cuenta. Queda en la auditoría. La IA debe mostrar los cambios, sobre todo los bancarios, y confirmar. Los datos son reversibles al editar; un certificado reemplazado puede no recuperarse.';
}
