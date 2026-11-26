<?php

namespace App\Mcp\Prompts\Role;

use App\Mcp\Prompts\RoutePrompt;

class RoleStorePrompt extends RoutePrompt
{
    protected string $name = 'role_store';

    protected string $title = 'Crear un rol';

    protected string $description = 'Crear un rol';

    protected string $method = 'POST';

    protected string $path = '/authorization/roles/store';

    protected string $business = 'Crea un rol nuevo (agrupación de permisos) con nombre técnico, título y descripción. Se usa cuando se necesita una categoría de autorización nueva. Se rechaza si el nombre, el título o la descripción ya existen en otro rol. El rol nace vacío: no incluye permisos ni se asigna a nadie por sí mismo; los permisos se le ligan al crearlos o editarlos, y los usuarios reciben roles al crearse según su cargo o al asignarles un permiso. El alta queda en la auditoría. Antes de ejecutarlo, la IA debe confirmar nombre técnico, título y descripción. No hay ruta para eliminar roles en esta lista; la corrección se hace editándolo, de modo que el alta no es fácil de deshacer.';
}
