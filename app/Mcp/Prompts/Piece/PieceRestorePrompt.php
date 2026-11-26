<?php

namespace App\Mcp\Prompts\Piece;

use App\Mcp\Prompts\RoutePrompt;

class PieceRestorePrompt extends RoutePrompt
{
    protected string $name = 'piece_restore';

    protected string $title = 'Restaurar una pieza desactivada';

    protected string $description = 'Restaurar una pieza desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/pieces/restore/{id}';

    protected string $business = 'Reactiva un registro de piece que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un piece. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.

Notas: Solo acepta registros desactivados.';
}
