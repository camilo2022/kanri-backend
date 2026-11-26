<?php

namespace App\Mcp\Prompts\Movement;

use App\Mcp\Prompts\RoutePrompt;

class MovementFindPrompt extends RoutePrompt
{
    protected string $name = 'movement_find';

    protected string $title = 'Obtener un movimiento con sus traslados';

    protected string $description = 'Obtener un movimiento con sus traslados';

    protected string $method = 'GET';

    protected string $path = '/movements/find/{id}';

    protected string $business = 'Trae un movimiento por su id numérico con todo lo necesario para ver su resumen y decidir un traslado: los traslados de entrada y salida con sus cantidades por talla, las redistribuciones (préstamos entre tallas dentro de un traslado), quién creó o modificó cada traslado, el proceso con sus procesos anterior y siguiente, y la curva del lote con las tallas de la marca. Un traslado es el envío de unidades de un lote de un proceso a otro y nace en estado Pendiente; luego el proceso que recibe lo aprueba o rechaza, y el que envía lo puede cancelar. Úsala con frases como «muéstrame el saldo del lote…» o «¿cuántas unidades puedo trasladar de este movimiento?». El campo transferable indica el tope de unidades que se pueden trasladar a otro proceso, calculado con lo que ya pasó por todos los pasos de la ficha menos lo ya trasladado; null significa que el proceso no mide productividad y no hay tope. Solo consulta.';
}
