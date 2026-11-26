<?php

namespace App\Mcp\Prompts\SupplierType;

use App\Mcp\Prompts\RoutePrompt;

class SupplierTypeAllPrompt extends RoutePrompt
{
    protected string $name = 'supplier_type_all';

    protected string $title = 'Listar los tipos de proveedor';

    protected string $description = 'Listar los tipos de proveedor';

    protected string $method = 'GET';

    protected string $path = '/typification/supplier_types/all';

    protected string $business = 'Devuelve la lista de tipos de proveedor registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar tipos de proveedor, o cuando necesites el id de uno para otra acción.';
}
