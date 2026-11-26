<?php

namespace App\Mcp\Prompts\Size;

use App\Mcp\Prompts\RoutePrompt;

class SizeUpdatePrompt extends RoutePrompt
{
    protected string $name = 'size_update';

    protected string $title = 'Editar una talla';

    protected string $description = 'Editar una talla';

    protected string $method = 'PUT';

    protected string $path = '/sizes/update/{id}';

    protected string $business = 'Edita una talla: nombre, descripción, código y si es producto terminado. Nombre, descripción y código se convierten a mayúsculas y se recortan antes de validar. Se RECHAZA si la talla no existe, si el nombre o el código ya los tiene otra talla, o si es producto terminado y el campo incumple el formato configurado en tallas; ese formato solo se revisa cuando el campo cambió, cuando la talla no era antes de producto terminado, o cuando el formato usa {talla} y el código cambió. Cambiar el código puede afectar a lo que referencia esa talla; no se pudo confirmar el alcance. Confirma con la persona los valores finales. Es reversible volviendo a editar.';
}
