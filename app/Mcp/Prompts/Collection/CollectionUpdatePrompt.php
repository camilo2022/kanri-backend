<?php

namespace App\Mcp\Prompts\Collection;

use App\Mcp\Prompts\RoutePrompt;

class CollectionUpdatePrompt extends RoutePrompt
{
    protected string $name = 'collection_update';

    protected string $title = 'Editar una colección';

    protected string $description = 'Editar una colección';

    protected string $method = 'PUT';

    protected string $path = '/collections/update/{id}';

    protected string $business = 'Edita nombre, descripción, código y fechas de una colección existente. Reglas: el nombre sigue siendo único; el código no puede repetirse en otra colección (incluso desactivada); el rango de fechas no puede cruzarse con otra; la descripción en mayúsculas. Para que una colección antigua siga editable si cambia la nomenclatura, el formato (regex) del nombre, descripción y código solo se revisa si ese campo cambió, o si usa partes del nombre y el nombre cambió. Si el nombre cambia y el formato tiene número y año, el número debe ser el siguiente al último de su año, sin contarse a sí misma. Efecto: actualiza los datos; las fichas técnicas ya ligadas conservan su relación. Confirmar con la persona qué colección y qué valores cambian. Es reversible editando de nuevo.';
}
