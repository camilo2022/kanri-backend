<?php

namespace App\Mcp\Prompts\Supply;

use App\Mcp\Prompts\RoutePrompt;

class SupplyStorePrompt extends RoutePrompt
{
    protected string $name = 'supply_store';

    protected string $title = 'Crear un insumo';

    protected string $description = 'Crear un insumo';

    protected string $method = 'POST';

    protected string $path = '/typification/supply_types/supplies/store';

    protected string $business = 'Crea un insumo de un tipo de insumo. Los campos que se piden y sus reglas (obligatorios, formatos, listas, fotos) los define el formulario configurado en el tipo de insumo, por lo que la IA debe consultar antes el tipo para saber qué datos pedir. Los valores comunes se guardan en la configuración del insumo; los campos que apuntan a catálogos («selectdinamic») se guardan como relaciones; las fotos se suben como archivos; y las listas «asociado a» validan que los elementos estén elegidos en el campo del que dependen y que las tallas estén permitidas. Se rechaza si el nombre ya existe, si un elemento no pertenece al grupo configurado o si una foto no es JPG o PNG de máximo 2 MB. Queda en la auditoría. La IA debe confirmar todos los datos. Es reversible: se puede editar o desactivar.';
}
