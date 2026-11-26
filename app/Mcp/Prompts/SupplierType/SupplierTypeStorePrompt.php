<?php

namespace App\Mcp\Prompts\SupplierType;

use App\Mcp\Prompts\RoutePrompt;

class SupplierTypeStorePrompt extends RoutePrompt
{
    protected string $name = 'supplier_type_store';

    protected string $title = 'Crear un tipo de proveedor';

    protected string $description = 'Crear un tipo de proveedor';

    protected string $method = 'POST';

    protected string $path = '/typification/supplier_types/store';

    protected string $business = 'Crea un registro nuevo de supplier type. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar supplier type. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.';
}
