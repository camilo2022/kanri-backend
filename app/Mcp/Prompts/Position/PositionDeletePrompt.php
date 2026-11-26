<?php

namespace App\Mcp\Prompts\Position;

use App\Mcp\Prompts\RoutePrompt;

class PositionDeletePrompt extends RoutePrompt
{
    protected string $name = 'position_delete';

    protected string $title = 'Desactivar un cargo';

    protected string $description = 'Desactivar un cargo';

    protected string $method = 'DELETE';

    protected string $path = '/organizational_structure/areas/positions/delete/{id}';

    protected string $business = 'Desactiva (borrado lógico) un cargo; se usa cuando el cargo ya no existe en la organización. Solo se rechaza si el cargo no existe o ya está desactivado: el Request no revisa si tiene empleados asignados, por lo que no se pudo confirmar qué pasa con ellos (la relación del empleado con el cargo no se modifica en este código). Efecto: el cargo queda con fecha de eliminación y sale de los listados activos; queda auditado. La IA debe confirmar el nombre del cargo y advertir que es una desactivación, no un borrado físico. Es reversible con la ruta de restaurar cargo.';
}
