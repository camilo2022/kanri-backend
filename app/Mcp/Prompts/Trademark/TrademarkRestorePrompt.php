<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkRestorePrompt extends RoutePrompt
{
    protected string $name = 'trademark_restore';

    protected string $title = 'Restaurar una marca desactivada';

    protected string $description = 'Restaurar una marca desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/trademarks/restore/{id}';

    protected string $business = 'Reactiva una marca desactivada con sus datos, tallas y validaciones previas. Se rechaza si la marca no existe o no está desactivada. No se revisa que su grupo siga activo; no se pudo confirmar ese caso. Queda en la auditoría. Antes de ejecutarlo, la IA debe nombrar la marca y pedir confirmación. Es reversible: se puede volver a desactivar.';
}
