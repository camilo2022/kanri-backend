<?php

namespace App\Mcp\Prompts\Collection;

use App\Mcp\Prompts\RoutePrompt;

class CollectionDeletePrompt extends RoutePrompt
{
    protected string $name = 'collection_delete';

    protected string $title = 'Desactivar una colección';

    protected string $description = 'Desactivar una colección';

    protected string $method = 'DELETE';

    protected string $path = '/collections/delete/{id}';

    protected string $business = 'Desactiva (borrado lógico) una colección que esté activa. Se rechaza si el id no existe, no es una colección o ya está desactivada. No se encontró validación que impida desactivar una colección con fichas técnicas u órdenes asociadas, así que debe advertirse a la persona. Efectos: el registro deja de aparecer en los catálogos activos (los formularios exigen colecciones no eliminadas), pero se conserva y cuenta para las validaciones de código, consecutivo de número y cruce de fechas de nuevas colecciones. Antes de ejecutar, confirmar el nombre y código de la colección. Es reversible con «collections/restore».';
}
