<?php

namespace App\Mcp\Prompts\Position;

use App\Mcp\Prompts\RoutePrompt;

class PositionUpdatePrompt extends RoutePrompt
{
    protected string $name = 'position_update';

    protected string $title = 'Editar un cargo';

    protected string $description = 'Editar un cargo';

    protected string $method = 'PUT';

    protected string $path = '/organizational_structure/areas/positions/update/{id}';

    protected string $business = 'Edita el nombre, la descripción y el área de un cargo existente. Se rechaza si el cargo no existe, si el nuevo nombre ya lo usa otro cargo o si el área no existe; textos en mayúsculas. Efecto: actualiza el cargo y reemplaza su área por la nueva (sync directo sobre la relación, sin el método de auditoría de relaciones que usan otros controladores). No toca los permisos del cargo. No se encontró validación que impida cambiar el área de un cargo con empleados asignados. La IA debe confirmar el cargo que se edita, los valores nuevos y, si cambia el área, que la persona lo desea. Es reversible: se puede volver a editar con los valores anteriores.';
}
