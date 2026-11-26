<?php

namespace App\Mcp\Prompts\Builder;

use App\Mcp\Prompts\RoutePrompt;

class BuilderUpdatePrompt extends RoutePrompt
{
    protected string $name = 'builder_update';

    protected string $title = 'Editar un constructor de curvas';

    protected string $description = 'Editar un constructor de curvas';

    protected string $method = 'PUT';

    protected string $path = '/cache/builders/update/{uuid}';

    protected string $business = 'Reemplaza por completo un constructor de curvas existente (marcas, categorías, subcategorías y porcentajes por talla). Se rechaza si el uuid no existe en la caché, si los porcentajes no suman exactamente 100, si una talla no existe o no pertenece a alguna de las marcas elegidas, si hay subcategorías sin categoría o ajenas a las categorías, o si alguna combinación ya pertenece a otro constructor (las del propio constructor se permiten). Efectos: se borran las claves de índice anteriores y se crean las nuevas según las combinaciones enviadas; el identificador no cambia. Se guarda en la caché de Laravel, no en la base de datos. Antes de ejecutar, confirmar cuál constructor se modifica y que se reemplazarán todos sus valores, no solo los enviados. Es reversible volviendo a editar con los valores anteriores.';
}
