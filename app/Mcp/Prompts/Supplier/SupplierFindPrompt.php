<?php

namespace App\Mcp\Prompts\Supplier;

use App\Mcp\Prompts\RoutePrompt;

class SupplierFindPrompt extends RoutePrompt
{
    protected string $name = 'supplier_find';

    protected string $title = 'Obtener un proveedor';

    protected string $description = 'Obtener un proveedor';

    protected string $method = 'GET';

    protected string $path = '/typification/supplier_types/suppliers/find/{id}';

    protected string $business = 'Muestra un proveedor con su detalle completo. Úsala cuando pregunten por un proveedor puntual, su contacto o sus datos para pagos. Trae su tipo de proveedor, su persona de contacto (con tipo de documento, tipo de persona, país del teléfono y ubicación con ciudad, departamento y país) y su cuenta bancaria con el certificado bancario. Incluye documento, correo, teléfono, dirección y datos bancarios: son sensibles, no repetirlos sin necesidad. Es solo consulta; si no existe responde que no se encontró.';
}
