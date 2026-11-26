<?php

namespace App\Mcp\Prompts\Movement;

use App\Mcp\Prompts\RoutePrompt;

class MovementAllPrompt extends RoutePrompt
{
    protected string $name = 'movement_all';

    protected string $title = 'Listar los movimientos';

    protected string $description = 'Listar los movimientos';

    protected string $method = 'GET';

    protected string $path = '/movements/all';

    protected string $business = 'Lista los movimientos: cada movimiento representa las unidades de un lote (la curva de una referencia con su lote) dentro de un proceso, y su estado es Ingreso, Movimiento o Salida según cuánto de lo recibido ya se envió a otro proceso. Sirve para preguntas como «¿qué movimientos tiene el lote BO001-A?» o «¿en qué procesos hay unidades de esta referencia?». Admite búsqueda por texto (marca, referencia y lote, colección, tipo de prenda, color, categoría), orden y paginación con per_page y page; sin per_page devuelve todo y la respuesta puede ser muy pesada. Cada movimiento trae el proceso, la curva del lote con la orden, la ficha técnica, la colección y el producto, y sus traslados de entrada y salida con las cantidades por talla. Para ver el resumen completo de un movimiento, con redistribuciones y el tope trasladable, está la consulta de un movimiento por id. Solo lee: no aprueba, rechaza ni crea traslados.';
}
