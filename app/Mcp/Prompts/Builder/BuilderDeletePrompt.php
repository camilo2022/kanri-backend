<?php

namespace App\Mcp\Prompts\Builder;

use App\Mcp\Prompts\RoutePrompt;

class BuilderDeletePrompt extends RoutePrompt
{
    protected string $name = 'builder_delete';

    protected string $title = 'Eliminar un constructor de curvas';

    protected string $description = 'Eliminar un constructor de curvas';

    protected string $method = 'DELETE';

    protected string $path = '/cache/builders/delete/{uuid}';

    protected string $business = 'Elimina un constructor de curvas de la caché: borra el constructor, sus claves de índice por marca, categoría y subcategoría, y lo quita del listado de ids. Se rechaza únicamente si el uuid no existe. No comprueba si el constructor se está usando; las órdenes ya creadas no cambian porque solo se usa como sugerencia. No queda registro de auditoría en base de datos, por tratarse de la caché. Es irreversible: para recuperarlo habría que crearlo de nuevo con los mismos marcas, categorías, subcategorías y porcentajes, que la IA debería mostrar a la persona antes de borrar. Confirmar con la persona exactamente cuál constructor (marcas y combinaciones) se eliminará.';
}
