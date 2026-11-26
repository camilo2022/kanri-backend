<?php

namespace App\Mcp\Prompts\Trademark;

use App\Mcp\Prompts\RoutePrompt;

class TrademarkSettingPrompt extends RoutePrompt
{
    protected string $name = 'trademark_setting';

    protected string $title = 'Configurar las reglas de código de una marca';

    protected string $description = 'Configurar las reglas de código de una marca';

    protected string $method = 'PUT';

    protected string $path = '/trademarks/setting/{id}';

    protected string $business = 'Configura las validaciones de referencia de una marca: una lista de reglas, cada una con una expresión regular y un mensaje de error, que sirven para comprobar el formato de las referencias de esa marca. Reemplaza toda la lista de validaciones (hay que enviarla completa, con al menos una) y conserva el código de la marca. Se rechaza si una expresión regular no es válida, si dos reglas tienen la misma expresión o si el identificador de una regla se repite. Cada regla lleva su fecha de creación, y puede marcarse como eliminada con fecha, de modo que desactivar una regla se hace dentro de la lista. Queda en la auditoría. La IA debe mostrar las reglas y confirmar: afectan a futuras referencias, no a las existentes. Es reversible volviendo a enviar la lista anterior.';
}
