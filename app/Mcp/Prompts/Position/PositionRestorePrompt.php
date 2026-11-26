<?php

namespace App\Mcp\Prompts\Position;

use App\Mcp\Prompts\RoutePrompt;

class PositionRestorePrompt extends RoutePrompt
{
    protected string $name = 'position_restore';

    protected string $title = 'Restaurar un cargo desactivado';

    protected string $description = 'Restaurar un cargo desactivado';

    protected string $method = 'PATCH';

    protected string $path = '/organizational_structure/areas/positions/restore/{id}';

    protected string $business = 'Reactiva un cargo que había sido desactivado. Se rechaza si el cargo no existe o no está desactivado (solo acepta cargos eliminados lógicamente). Efecto: quita la fecha de eliminación y el cargo vuelve a los listados y a poder asignarse; conserva su área y permisos anteriores. No hay validación de nombre duplicado al restaurar, así que no se pudo confirmar qué ocurre si otro cargo activo tomó el mismo nombre. La IA debe confirmar con la persona cuál cargo desactivado quiere reactivar. Es reversible: se puede volver a desactivar con la ruta de eliminar.';
}
