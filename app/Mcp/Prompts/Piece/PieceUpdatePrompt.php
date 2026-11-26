<?php

namespace App\Mcp\Prompts\Piece;

use App\Mcp\Prompts\RoutePrompt;

class PieceUpdatePrompt extends RoutePrompt
{
    protected string $name = 'piece_update';

    protected string $title = 'Editar una pieza';

    protected string $description = 'Editar una pieza';

    protected string $method = 'PUT';

    protected string $path = '/pieces/update/{id}';

    protected string $business = 'Modifica un registro existente de piece, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un piece. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.

Notas: Reemplaza nombre y descripción.';
}
