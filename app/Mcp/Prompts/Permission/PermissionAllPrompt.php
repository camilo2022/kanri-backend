<?php

namespace App\Mcp\Prompts\Permission;

use App\Mcp\Prompts\RoutePrompt;

class PermissionAllPrompt extends RoutePrompt
{
    protected string $name = 'permission_all';

    protected string $title = 'Listar los permisos';

    protected string $description = 'Listar los permisos';

    protected string $method = 'GET';

    protected string $path = '/authorization/permissions/all';

    protected string $business = 'Lista los permisos del sistema, que son las acciones que se pueden otorgar a un rol, a un cargo o directamente a un usuario. Úsala cuando pregunten qué permisos existen, qué permiso corresponde a cierta pantalla o qué permisos tiene un rol. Cada permiso trae nombre, título y descripción, y el rol al que pertenece (solo el primero si estuviera en varios). Se puede filtrar por rol con role_id y buscar por texto en el nombre, el título o la descripción del permiso y de su rol. No existen permisos eliminados ni inactivos: no hay with_trashed. Sin per_page devuelve todos en una sola página.';
}
