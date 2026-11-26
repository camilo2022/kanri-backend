<?php

namespace App\Mcp\Prompts\Module;

use App\Mcp\Prompts\RoutePrompt;

class ModuleUpdatePrompt extends RoutePrompt
{
    protected string $name = 'module_update';

    protected string $title = 'Editar un módulo';

    protected string $description = 'Editar un módulo';

    protected string $method = 'PUT';

    protected string $path = '/navegation/modules/update/{id}';

    protected string $business = 'Edita el nombre y el icono de un módulo del menú de navegación. No modifica los submódulos que ya tiene. Se RECHAZA si el módulo no existe, si el nombre coincide con el de otro módulo (activo o eliminado), si pasa de 18 caracteres o tiene un formato no permitido (solo letras con tildes y ñ separadas por espacios o por punto y espacio), o si el icono no cumple el formato Fa… de Font Awesome. No exige mayúsculas. Confirma con la persona cuál módulo cambia y los nuevos valores, pues el menú de todos los usuarios cambia. Es reversible volviendo a editarlo.';
}
