<?php

namespace App\Mcp\Prompts\User;

use App\Mcp\Prompts\RoutePrompt;

class UserStorePrompt extends RoutePrompt
{
    protected string $name = 'user_store';

    protected string $title = 'Crear un usuario';

    protected string $description = 'Crear un usuario';

    protected string $method = 'POST';

    protected string $path = '/users/store';

    protected string $business = 'Crea el usuario de acceso a Kanri de un empleado. Se rechaza si el empleado no existe o está desactivado, si ya tiene usuario, o si no tiene un cargo activo (de ahí salen los permisos). Efectos automáticos: el nombre de usuario se genera solo con un procedimiento de la base de datos a partir de los nombres completos de la persona más la inicial de cada apellido (con número si ya existe), y en pantalla se muestra con @; la IA no debe pedirlo ni inventarlo. El usuario recibe los roles y permisos del cargo del empleado, es decir, define lo que podrá hacer en Kanri. La contraseña se guarda cifrada y debe confirmarse. Queda en la auditoría. La IA debe confirmar el empleado y avisar qué permisos heredará; nunca debe escoger ni mostrar contraseñas por iniciativa propia. Es reversible desactivando el usuario.';
}
