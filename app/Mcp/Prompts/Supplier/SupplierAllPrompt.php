<?php

namespace App\Mcp\Prompts\Supplier;

use App\Mcp\Prompts\RoutePrompt;

class SupplierAllPrompt extends RoutePrompt
{
    protected string $name = 'supplier_all';

    protected string $title = 'Listar los proveedores de un tipo de proveedor';

    protected string $description = 'Listar los proveedores de un tipo de proveedor';

    protected string $method = 'GET';

    protected string $path = '/typification/supplier_types/suppliers/all/{supplier_type_id}';

    protected string $business = 'Lista los proveedores de un tipo de proveedor; el id del tipo va en la ruta. Úsala cuando pregunten qué proveedores hay de cierto tipo, por ejemplo los de determinado servicio o insumo. Cada proveedor llega con su tipo, pero en esta lista no trae su persona de contacto ni su cuenta bancaria; para eso se consulta el proveedor individual. Admite búsqueda por texto y with_trashed para incluir los inactivos.';
}
