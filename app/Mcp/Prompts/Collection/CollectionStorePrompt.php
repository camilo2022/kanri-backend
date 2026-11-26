<?php

namespace App\Mcp\Prompts\Collection;

use App\Mcp\Prompts\RoutePrompt;

class CollectionStorePrompt extends RoutePrompt
{
    protected string $name = 'collection_store';

    protected string $title = 'Crear una colección';

    protected string $description = 'Crear una colección';

    protected string $method = 'POST';

    protected string $path = '/collections/store';

    protected string $business = 'Crea una colección (registro del catálogo de colecciones). Reglas: el nombre es único y debe cumplir el formato configurado en el ítem de colecciones (si hay uno); la descripción va en mayúsculas; el código en settings.code debe ser único incluso contra colecciones desactivadas y cumplir su formato; el número de la colección es consecutivo dentro de su año (si el formato incluye número y año, debe ser el siguiente al último, contando las desactivadas); las fechas de inicio y fin deben ser válidas y el fin posterior al inicio; y el rango no puede cruzarse con el de ninguna otra colección, incluidas las desactivadas. Las partes del nombre pueden reemplazar marcadores como {numero} en la descripción y el código. Efecto: crea el registro con nombre, descripción y settings (código, fechas). Las fichas técnicas se asocian a la colección después. Confirmar nombre, descripción en mayúsculas, código y fechas. Es reversible con eliminar (desactiva) y restaurar.';
}
