<?php

namespace App\Mcp\Prompts\Position;

use App\Mcp\Prompts\RoutePrompt;

class PositionStorePrompt extends RoutePrompt
{
    protected string $name = 'position_store';

    protected string $title = 'Crear un cargo';

    protected string $description = 'Crear un cargo';

    protected string $method = 'POST';

    protected string $path = '/organizational_structure/areas/positions/store';

    protected string $business = 'Crea un cargo (posición) dentro de un área de la estructura organizacional; se usa al definir qué cargos existen y a qué área pertenecen. Se rechaza si el área no existe o si el nombre ya lo tiene otro cargo; nombre y descripción deben ir en mayúsculas. Efecto: guarda el cargo (con auditoría) y lo vincula al área indicada en area_id; nace sin permisos ni roles, que se le dan después con la ruta de asignar autorización. Antes de ejecutarlo, la IA debe confirmar con la persona el nombre, la descripción y el área. Es reversible: el cargo se puede editar o desactivar (borrado lógico) y restaurar.';
}
