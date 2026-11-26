<?php

namespace App\Mcp\Prompts\Size;

use App\Mcp\Prompts\RoutePrompt;

class SizeSettingPrompt extends RoutePrompt
{
    protected string $name = 'size_setting';

    protected string $title = 'Configurar el formato de las tallas';

    protected string $description = 'Configurar el formato de las tallas';

    protected string $method = 'PUT';

    protected string $path = '/sizes/setting/{id}';

    protected string $business = 'Guarda la configuración del ítem de Tallas (todo el catálogo, no una talla): la clave formats, con un formato por campo de la talla (nombre, descripción y código). Cada formato tiene una expresión regular y el mensaje que verá quien no la cumpla. El sistema aplica esos formatos solo al crear o editar tallas marcadas como producto terminado; en la regex, {talla} se reemplaza por el código escrito (el código no puede usar {talla}). Se RECHAZA si el id no es el del ítem de tallas, si una regex es inválida o si hay regex sin mensaje. Una regex vacía desactiva la revisión de ese campo. Solo se reemplaza formats; otras claves del ítem se conservan. Confirma los formatos con la persona. Es reversible reenviando los anteriores.';
}
