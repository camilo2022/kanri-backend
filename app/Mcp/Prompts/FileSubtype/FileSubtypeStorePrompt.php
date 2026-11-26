<?php

namespace App\Mcp\Prompts\FileSubtype;

use App\Mcp\Prompts\RoutePrompt;

class FileSubtypeStorePrompt extends RoutePrompt
{
    protected string $name = 'file_subtype_store';

    protected string $title = 'Crear un subtipo de archivo';

    protected string $description = 'Crear un subtipo de archivo';

    protected string $method = 'POST';

    protected string $path = '/typification/file_types/file_subtypes/store';

    protected string $business = 'Crea un registro nuevo de file subtype. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar file subtype. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.

Notas: Lo vincula al tipo de archivo indicado.';
}
