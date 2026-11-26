<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserAllPrompt extends RoutePrompt
{
    protected string $name = 'user_all';

    protected string $title = 'Listar los usuarios';

    protected string $description = 'Listar los usuarios';

    protected string $method = 'GET';

    protected string $path = '/users/all';

    protected string $business = 'Lista los usuarios que pueden entrar a Kanri. Un usuario pertenece a un empleado y tiene su propio nombre de usuario, sus roles y permisos. Úsala cuando pregunten quién tiene usuario, qué roles tiene alguien o qué usuarios están inactivos. Cada usuario llega con su empleado (con su persona y foto, y su cargo con área), sus roles y sus permisos directos; no trae contraseñas. Admite búsqueda por texto y with_trashed para incluir los usuarios inactivos (eliminados lógicamente). La persona del empleado trae datos sensibles como documento y correo: no repetirlos sin necesidad.';
}
