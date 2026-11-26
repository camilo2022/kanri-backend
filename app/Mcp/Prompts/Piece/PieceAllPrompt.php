<?php

namespace App\Mcp\Prompts\Piece;

use App\Mcp\Prompts\RoutePrompt;

class PieceAllPrompt extends RoutePrompt
{
    protected string $name = 'piece_all';

    protected string $title = 'Listar las piezas';

    protected string $description = 'Listar las piezas';

    protected string $method = 'GET';

    protected string $path = '/pieces/all';

    protected string $business = 'Lista el catálogo de piezas, es decir las partes de la prenda que se cortan y se asignan al lote en la orden de producción (por ejemplo bolsillos o pretina). Úsala cuando la persona pregunte «¿qué piezas hay?» o necesite el id de una pieza. Admite búsqueda, orden y paginación; with_trashed es obligatorio y con verdadero incluye también las piezas desactivadas. La lista no trae la configuración (settings) de cada pieza. La pueden consultar también quienes configuran procesos, subprocesos u operaciones, porque las piezas alimentan esas configuraciones. No dice en qué lotes se usa cada pieza.';
}
