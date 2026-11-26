<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkStorePrompt extends RoutePrompt
{
    protected string $name = 'trademark_store';

    protected string $title = 'Crear una marca';

    protected string $description = 'Crear una marca';

    protected string $method = 'POST';

    protected string $path = '/trademarks/store';

    protected string $business = 'Crea una marca de producto de la empresa. Campos clave: grupo al que pertenece (la marca queda ligada a ese grupo); nombre y descripción en mayúsculas; logo obligatorio (imagen, con su tipo y subtipo de archivo); portada opcional para el catálogo; y un código corto de la marca en la configuración (hasta 5 caracteres, único entre marcas). El código es parte de cómo se identifican las referencias. Se rechaza si el nombre o el código ya existen en otra marca. La marca nace sin tallas ni validaciones de referencia: las tallas se asignan con la asignación de tallas y las validaciones con la configuración de la marca. Queda en la auditoría. Antes de ejecutarlo, la IA debe confirmar nombre, código, grupo y el logo. Es reversible: se puede editar o desactivar. Nota: no se pudo confirmar si el grupo desactivado se rechaza.';
}
