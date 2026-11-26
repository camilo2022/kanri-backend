<?php

namespace App\Mcp\Prompts\Position;

use App\Mcp\Prompts\RoutePrompt;

class PositionAssignPrompt extends RoutePrompt
{
    protected string $name = 'position_assign';

    protected string $title = 'Asignar un permiso a un cargo';

    protected string $description = 'Asignar un permiso a un cargo';

    protected string $method = 'POST';

    protected string $path = '/organizational_structure/areas/positions/authorization/assign/{id}';

    protected string $business = 'Concede un permiso a un cargo; se usa para definir qué puede hacer quien ocupe ese cargo. Se rechaza si el cargo o el permiso no existen o si el cargo ya tiene ese permiso (mensaje «El cargo ya tiene este permiso»). Efecto: asigna el permiso al cargo y, si el cargo aún no tiene los roles a los que pertenece ese permiso, también se los asigna. Al crear un usuario nuevo se copian a él los roles y permisos de su cargo; no se encontró propagación automática a usuarios ya creados. La IA debe confirmar cargo y permiso exacto, por ser un cambio de seguridad. Es reversible con la ruta de quitar autorización.';
}
