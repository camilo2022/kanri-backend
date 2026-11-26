<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkSizeAssignPrompt extends RoutePrompt
{
    protected string $name = 'trademark_size_assign';

    protected string $title = 'Asignar una talla a una marca';

    protected string $description = 'Asignar una talla a una marca';

    protected string $method = 'POST';

    protected string $path = '/trademarks/size/assign/{id}';

    protected string $business = 'Asigna una talla a una marca, es decir, la habilita en esa marca. Se usa al definir qué tallas maneja cada marca. Se rechaza si la marca o la talla no existen o si la talla ya está asignada a esa marca. Efecto: se agrega una sola talla a la relación y queda registrada en la auditoría. No modifica las tallas de referencias u órdenes ya creadas; no se pudo confirmar si otras partes validan las tallas contra esta relación. Antes de ejecutarlo, la IA debe confirmar la marca y la talla. Es reversible con la ruta de quitar talla.';
}
