<?php

namespace App\Mcp\Prompts\ManagementCollection;

use App\Mcp\Prompts\RoutePrompt;

class ManagementCollectionFindPrompt extends RoutePrompt
{
    protected string $name = 'management_collection_find';

    protected string $title = 'Obtener una colección con sus fichas técnicas para gestionarlas';

    protected string $description = 'Obtener una colección con sus fichas técnicas para gestionarlas';

    protected string $method = 'GET';

    protected string $path = '/management/collections/find/{id}';

    protected string $business = 'Es la consulta de la pantalla Gestión de colecciones: trae una colección por su id numérico junto con todas sus fichas técnicas, para que la gerencia revise en qué estado está cada una. Úsala cuando la persona diga «muéstrame las fichas de la colección X» o «¿qué fichas de esta colección faltan por aprobar?». Cada ficha viene con su producto (marca, subcategoría y categoría), los insumos con su tipo, los detalles por proceso con su estado y tipología, el tipo de prenda, el tono de lavado, el tipo de bota y las fotos delantera y trasera. No trae lotes ni órdenes de producción (eso lo trae la consulta de gestión de producción) ni filtra por estado: llegan todas las fichas de la colección.';
}
