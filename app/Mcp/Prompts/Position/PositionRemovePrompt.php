<?php

namespace App\Mcp\Prompts\Position;

use App\Mcp\Prompts\RoutePrompt;

class PositionRemovePrompt extends RoutePrompt
{
    protected string $name = 'position_remove';

    protected string $title = 'Remover un permiso de un cargo';

    protected string $description = 'Remover un permiso de un cargo';

    protected string $method = 'POST';

    protected string $path = '/organizational_structure/areas/positions/authorization/remove/{id}';

    protected string $business = 'Retira un permiso a un cargo. Se rechaza si el cargo o el permiso no existen o si el cargo no tiene ese permiso asignado. Efecto: revoca el permiso del cargo y, después, quita del cargo los roles que ya no compartan ningún permiso con él (si el rol queda sin permisos en común, se retira). Los usuarios ya creados conservan lo que se les copió al crearlos: no se encontró propagación automática del cambio. La IA debe confirmar cargo y permiso, y advertir que puede dejar al cargo sin roles. Es reversible: se puede volver a asignar el permiso con la ruta de asignar, que devuelve también el rol.';
}
