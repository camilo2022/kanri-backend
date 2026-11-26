<?php

namespace App\Mcp\Prompts\Module;

use App\Mcp\Prompts\RoutePrompt;

class ModuleStorePrompt extends RoutePrompt
{
    protected string $name = 'module_store';

    protected string $title = 'Crear un módulo';

    protected string $description = 'Crear un módulo';

    protected string $method = 'POST';

    protected string $path = '/navegation/modules/store';

    protected string $business = 'Crea un módulo del menú de navegación de la aplicación (el grupo que agrupa submódulos). Se usa para añadir una sección nueva al menú. Se RECHAZA si el nombre ya existe (se compara también con módulos eliminados), si supera 18 caracteres o tiene caracteres que no sean letras (con tildes y ñ) separadas por espacios o por punto y espacio, y si el icono no tiene el formato de nombre de icono Font Awesome que empieza por Fa (por ejemplo FaUsers). Aquí no se exigen mayúsculas. El módulo nace sin submódulos. Confirma con la persona el nombre y el icono. Es reversible: se edita o elimina después.';
}
