<?php

namespace App\Mcp\Prompts\Collection;

use App\Mcp\Prompts\RoutePrompt;

class CollectionSettingPrompt extends RoutePrompt
{
    protected string $name = 'collection_setting';

    protected string $title = 'Configurar el formato de las colecciones';

    protected string $description = 'Configurar el formato de las colecciones';

    protected string $method = 'PUT';

    protected string $path = '/collections/setting/{id}';

    protected string $business = 'Guarda en el ítem de colecciones (`items` id 30, `settings.formats`) el regex y el mensaje de error del nombre, la descripción y el código. El regex del nombre define partes con nombre, `(?<numero>…)` y `(?<anio>…)`, que la descripción y el código usan como `{numero}` y `{anio}`. Con esas dos partes, el número también debe ser consecutivo dentro del año. Un regex vacío deja ese campo sin revisión de formato. Las demás llaves de `settings` del ítem se conservan.';
}
