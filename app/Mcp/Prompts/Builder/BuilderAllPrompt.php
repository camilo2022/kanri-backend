<?php

namespace App\Mcp\Prompts\Builder;

use App\Mcp\Prompts\RoutePrompt;

class BuilderAllPrompt extends RoutePrompt
{
    protected string $name = 'builder_all';

    protected string $title = 'Listar los constructores de curvas';

    protected string $description = 'Listar los constructores de curvas';

    protected string $method = 'GET';

    protected string $path = '/cache/builders/all';

    protected string $business = 'Lista los constructores de curvas, que son las plantillas con el porcentaje de producción por talla que se usa para armar la curva de un lote. Cada constructor aplica a una o varias marcas y, si se definió, a ciertas categorías y subcategorías. Úsala cuando la persona pregunte «¿qué porcentajes por talla tiene la marca X?» o «¿qué constructores de curva hay?». Cada constructor trae su id (un código uuid), las marcas, categorías y subcategorías a las que aplica, y la lista de tallas con su porcentaje. Los constructores no están en la base de datos sino en la memoria caché del sistema: si esa caché se limpia, desaparecen. La consulta no tiene filtros, búsqueda ni paginación: siempre devuelve todos. No muestra curvas de lotes concretos (eso está en la orden de producción).';
}
