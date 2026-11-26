<?php

namespace App\Mcp\Prompts\Country;

use App\Mcp\Prompts\RoutePrompt;

class CountrySettingsPrompt extends RoutePrompt
{
    protected string $name = 'country_settings';

    protected string $title = 'Guardar la configuración de un país';

    protected string $description = 'Guardar la configuración de un país';

    protected string $method = 'PUT';

    protected string $path = '/locations/countries/settings/{id}';

    protected string $business = 'Reemplaza la configuración (settings) de un país. La clave que guarda es validations: lista de reglas del número de teléfono de ese país, cada una con una expresión regular y un mensaje. El sistema las usa al registrar teléfonos de personas con ese país: se consideran las validaciones activas (no marcadas con deleted_at) y se informa el mensaje de la primera que no se cumpla. Se RECHAZA si el país no existe, si una regex no es válida o se repite, si un id de validación se repite, o si falta mensaje o fecha de creación. La lista puede ir vacía (sin validaciones). Lo enviado sustituye lo anterior: confirma con la persona la lista completa. Es reversible reenviando la anterior.';
}
