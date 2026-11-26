<?php

namespace App\Mcp\Prompts\Size;

use App\Mcp\Prompts\RoutePrompt;

class SizeDeletePrompt extends RoutePrompt
{
    protected string $name = 'size_delete';

    protected string $title = 'Desactivar una talla';

    protected string $description = 'Desactivar una talla';

    protected string $method = 'DELETE';

    protected string $path = '/sizes/delete/{id}';

    protected string $business = 'Elimina (borrado lógico) una talla del catálogo. Se RECHAZA si la talla no existe o ya está eliminada. En el código revisado no se comprueba si la talla se usa en fichas técnicas, órdenes o inventario, así que no se puede asegurar que el sistema lo impida. No cambia otros datos; el código de la talla sigue ocupado para la unicidad mientras esté eliminada. Antes de ejecutarlo, confirma con la persona la talla (código y nombre). Es reversible con la ruta de restauración.';
}
