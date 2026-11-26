<?php

namespace App\Mcp\Prompts\Typology;

use App\Mcp\Prompts\RoutePrompt;

class TypologyAllPrompt extends RoutePrompt
{
    protected string $name = 'typology_all';

    protected string $title = 'Listar las tipologías';

    protected string $description = 'Listar las tipologías';

    protected string $method = 'GET';

    protected string $path = '/typification/typologies/all';

    protected string $business = 'Lista las tipologías, que son las clasificaciones que se le dan a una referencia dentro de cada proceso (por ejemplo para el cronograma de producción). Cada tipología trae los procesos a los que pertenece y los detalles de ficha técnica que la usan. Úsala cuando la persona pregunte «¿qué tipologías hay para el proceso X?». Filtro propio: process_id, que deja solo las tipologías de ese proceso. Admite búsqueda, orden y paginación; sin per_page devuelve todas; with_trashed es obligatorio e incluye las desactivadas. No indica la tipología de una referencia concreta: eso está en los detalles de su ficha técnica.';
}
