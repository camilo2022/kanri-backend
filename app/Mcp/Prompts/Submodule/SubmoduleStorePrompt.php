<?php

namespace App\Mcp\Prompts\Submodule;

use App\Mcp\Prompts\RoutePrompt;

class SubmoduleStorePrompt extends RoutePrompt
{
    protected string $name = 'submodule_store';

    protected string $title = 'Crear un submódulo';

    protected string $description = 'Crear un submódulo';

    protected string $method = 'POST';

    protected string $path = '/navegation/modules/submodules/store';

    protected string $business = 'Crea un submódulo: una entrada del menú que lleva a una pantalla (url) y que queda ligada a un módulo y a un permiso existente. Se usa para publicar una pantalla nueva en el menú. Se RECHAZA si el módulo o el permiso no existen, si el permiso ya está asignado a otro submódulo (cada permiso solo se usa una vez), si el nombre o la url ya existen (incluidos eliminados), si el nombre pasa de 18 caracteres o tiene formato inválido, si la url no es una ruta en minúsculas con guiones bajos que empieza por «/» (máximo 40) o si el icono no tiene formato Fa… Confirma con la persona módulo, permiso, url e icono. Es reversible: se edita o elimina.';
}
