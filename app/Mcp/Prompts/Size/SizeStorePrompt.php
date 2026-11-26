<?php

namespace App\Mcp\Prompts\Size;

use App\Mcp\Prompts\RoutePrompt;

class SizeStorePrompt extends RoutePrompt
{
    protected string $name = 'size_store';

    protected string $title = 'Crear una talla';

    protected string $description = 'Crear una talla';

    protected string $method = 'POST';

    protected string $path = '/sizes/store';

    protected string $business = 'Crea una talla en el catálogo. La identidad de la talla es su código (settings.code); nombre y descripción son textos para mostrar. Se usa para añadir tallas de producto terminado o de otros usos. A diferencia de otros catálogos, aquí nombre, descripción y código se CONVIERTEN a mayúsculas y se limpian espacios antes de validar. Se RECHAZA si el nombre o el código ya existen (el código es único entre tallas) o si faltan. Si finished_product es verdadero (producto terminado), nombre, descripción y código deben cumplir los formatos (regex) configurados en la configuración de tallas, donde {talla} se sustituye por el código; con otra clase de talla no se revisa formato. Confirma con la persona código, nombre, descripción y si es producto terminado. Es reversible.';
}
