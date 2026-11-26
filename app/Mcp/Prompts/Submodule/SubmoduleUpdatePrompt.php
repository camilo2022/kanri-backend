<?php

namespace App\Mcp\Prompts\Submodule;

use App\Mcp\Prompts\RoutePrompt;

class SubmoduleUpdatePrompt extends RoutePrompt
{
    protected string $name = 'submodule_update';

    protected string $title = 'Editar un submódulo';

    protected string $description = 'Editar un submódulo';

    protected string $method = 'PUT';

    protected string $path = '/navegation/modules/submodules/update/{id}';

    protected string $business = 'Edita un submódulo del menú: nombre, url, icono, el módulo al que pertenece y el permiso que lo controla; permite moverlo a otro módulo. Se RECHAZA si el submódulo, el módulo o el permiso no existen, si el permiso ya lo usa otro submódulo, si el nombre o la url duplican a otro (activo o eliminado), si el nombre pasa de 18 caracteres o tiene formato inválido, si la url no es una ruta en minúsculas con guiones bajos iniciada por «/», o si el icono no tiene formato Fa… Cambiar el permiso modifica quién ve la entrada. Confirma con la persona los cambios. Es reversible volviendo a editar.';
}
