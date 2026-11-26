<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkUpdatePrompt extends RoutePrompt
{
    protected string $name = 'trademark_update';

    protected string $title = 'Editar una marca';

    protected string $description = 'Editar una marca';

    protected string $method = 'PUT';

    protected string $path = '/trademarks/update/{id}';

    protected string $business = 'Edita la marca: grupo, nombre, descripción, código y, opcionalmente, nuevo logo o nueva portada (el archivo nuevo reemplaza al anterior del mismo tipo). El campo del logo se debe enviar, pero el archivo es opcional al editar. Conserva las validaciones de referencia ya configuradas: solo se cambia el código. Se rechaza si el nombre o el código lo usa otra marca. Cambiar el código o el grupo afecta cómo se identifican y agrupan las referencias de esa marca; la IA debe advertirlo. Queda en la auditoría. Antes de ejecutarlo, la IA debe mostrar el cambio y confirmar. El texto es reversible volviendo a editar; una imagen reemplazada no se recupera.';
}
