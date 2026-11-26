<?php

namespace App\Mcp\Prompts\Process;

use App\Mcp\Prompts\RoutePrompt;

class ProcessRestorePrompt extends RoutePrompt
{
    protected string $name = 'process_restore';

    protected string $title = 'Restaurar un proceso';

    protected string $description = 'Restaurar un proceso';

    protected string $method = 'PATCH';

    protected string $path = '/workflow/processes/restore/{id}';

    protected string $business = 'Reactiva un proceso desactivado. Se rechaza si el proceso no existe o no está desactivado. Efecto: quita la fecha de eliminación y el proceso vuelve al catálogo y a la línea del flujo con la configuración y los enlaces que tenía; no valida de nuevo unicidad de nombre, color ni lugar de producción, por lo que no se pudo confirmar qué pasa si otro proceso tomó ese nombre o color. La IA debe confirmar cuál proceso reactivar y avisar que puede cambiar quién es el primer proceso del flujo. Es reversible con la ruta de eliminar proceso.';
}
