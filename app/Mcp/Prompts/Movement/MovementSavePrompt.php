<?php

namespace App\Mcp\Prompts\Movement;

use App\Mcp\Prompts\RoutePrompt;

class MovementSavePrompt extends RoutePrompt
{
    protected string $name = 'movement_save';

    protected string $title = 'Trasladar unidades a otro proceso (o editar un traslado)';

    protected string $description = 'Trasladar unidades a otro proceso (o editar un traslado)';

    protected string $method = 'POST';

    protected string $path = '/movements/save';

    protected string $business = 'Registra un traslado de unidades de un proceso (origen) a otro (destino) del mismo lote, o edita uno que ya existe si se envía movement_detail_id. Al crear: busca el movimiento del proceso origen (si no existe, falla), busca o crea el movimiento del proceso destino en estado «Ingreso» y crea el traslado en estado «Pendiente» con sus cantidades por talla y las redistribuciones entre tallas. Luego recalcula el estado del movimiento origen: Ingreso (nada enviado), Movimiento (envió una parte o hay envíos pendientes) o Salida (envió todo y se lo aceptaron). Se rechaza si el destino no es el mismo proceso ni uno anterior o siguiente del origen, si se piden más unidades de las que ya pasaron por todos los puestos que exige la ficha (menos lo ya trasladado), o si una talla no tiene unidades suficientes para prestar en una redistribución. Al editar, la validación de «solo pendientes» solo se aplica si se envía status=Aprobado; al reducir una cantidad se reducen las redistribuciones de esa talla. La curva del lote se recalcula al cambiar de estado (aprobar, rechazar o cancelar); no se pudo confirmar que se recalcule al guardar. Antes de ejecutar, confirmar proceso origen y destino, tallas, cantidades, redistribuciones y observación. Es reversible solo mientras siga Pendiente (cancelar).';
}
