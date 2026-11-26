<?php

namespace App\Mcp\Prompts\Role;

use App\Mcp\Prompts\RoutePrompt;

class RoleAllPrompt extends RoutePrompt
{
    protected string $name = 'role_all';

    protected string $title = 'Listar los roles';

    protected string $description = 'Listar los roles';

    protected string $method = 'GET';

    protected string $path = '/authorization/roles/all';

    protected string $business = 'Lista los roles, que son conjuntos de permisos que luego se asignan a cargos o a usuarios. Úsala cuando pregunten qué roles hay, qué puede hacer un rol o quién podría hacer cierta acción. Cada rol llega con su lista de permisos, y se puede buscar por nombre, título o descripción. No hay borrado lógico ni with_trashed: lo que existe se ve. Sin per_page devuelve todos, lo cual puede ser bastante porque cada rol trae todos sus permisos.';
}
