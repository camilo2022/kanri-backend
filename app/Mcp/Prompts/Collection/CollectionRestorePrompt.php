<?php

namespace App\Mcp\Prompts\Collection;

use App\Mcp\Prompts\RoutePrompt;

class CollectionRestorePrompt extends RoutePrompt
{
    protected string $name = 'collection_restore';

    protected string $title = 'Restaurar una colección desactivada';

    protected string $description = 'Restaurar una colección desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/collections/restore/{id}';

    protected string $business = 'Reactiva una colección previamente desactivada. Se rechaza si el id no corresponde a una colección o si esta no está desactivada (solo acepta registros con borrado lógico). No revalida el código, el consecutivo de número ni el cruce de fechas: como la creación y edición cuentan las desactivadas para esas reglas, normalmente no habrá conflicto, pero no se pudo confirmar que no pueda quedar un rango cruzado si las reglas cambiaron. Efecto: el registro vuelve a estar activo y disponible para fichas y órdenes. Antes de ejecutar, confirmar con la persona cuál colección (nombre, código y fechas) se reactiva. Es reversible con «collections/delete».';
}
