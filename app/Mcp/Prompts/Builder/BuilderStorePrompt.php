<?php

namespace App\Mcp\Prompts\Builder;

use App\Mcp\Prompts\RoutePrompt;

class BuilderStorePrompt extends RoutePrompt
{
    protected string $name = 'builder_store';

    protected string $title = 'Crear un constructor de curvas';

    protected string $description = 'Crear un constructor de curvas';

    protected string $method = 'POST';

    protected string $path = '/cache/builders/store';

    protected string $business = 'Crea un constructor de curvas: una plantilla que reparte porcentajes por talla para una combinación de marcas, categorías y subcategorías. No se guarda en la base de datos sino en la caché de Laravel, sin vencimiento, con un uuid nuevo; no se pudo confirmar el driver de caché, por lo que podría perderse si se limpia. Se rechaza si los porcentajes (cuyas claves son identificadores de talla) no suman exactamente 100, si una talla no existe o no pertenece a alguna de las marcas elegidas, si se eligen subcategorías sin categoría o que no pertenecen a las categorías elegidas, o si alguna de las combinaciones (marca, marca-categoría, marca-categoría-subcategoría) ya pertenece a otro constructor. Sirve para sugerir la curva de producción al armar órdenes. Antes de ejecutar, confirmar marcas, categorías, subcategorías y el porcentaje de cada talla. Es reversible con la ruta de eliminar o editando.';
}
