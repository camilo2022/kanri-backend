<?php

namespace App\Mcp\Prompts\Permission;

use App\Mcp\Prompts\RoutePrompt;

class PermissionStorePrompt extends RoutePrompt
{
    protected string $name = 'permission_store';

    protected string $title = 'Crear un permiso';

    protected string $description = 'Crear un permiso';

    protected string $method = 'POST';

    protected string $path = '/authorization/permissions/store';

    protected string $business = 'Crea un permiso nuevo del catálogo de autorización de Kanri y lo asocia a un rol. Se usa cuando se agrega una capacidad nueva al sistema. El «nombre» es el identificador técnico que el sistema compara al autorizar una acción (por ejemplo «users.store»); el título y la descripción son los textos que ve la gente. Se rechaza si el nombre, el título o la descripción ya existen en otro permiso, o si el rol no existe. Efectos: el permiso queda sincronizado con el rol indicado (un único rol) y el cambio queda en la auditoría. Crear el permiso no se lo da a ningún usuario; eso se hace aparte. Un permiso sin una ruta que lo exija no controla nada: no se pudo confirmar desde aquí si la ruta correspondiente existe. Antes de ejecutarlo, la IA debe confirmar nombre, título, descripción y rol. No hay una ruta para eliminarlo; solo se puede corregir con la edición.';
}
