<?php

namespace App\Mcp\Prompts\Rule;

use App\Mcp\Prompts\RoutePrompt;

class RuleSettingPrompt extends RoutePrompt
{
    protected string $name = 'rule_setting';

    protected string $title = 'Configurar los pesos de las reglas';

    protected string $description = 'Configurar los pesos de las reglas';

    protected string $method = 'PUT';

    protected string $path = '/rules/setting/{id}';

    protected string $business = 'Guarda la configuración del ítem de Reglas (el catálogo completo de reglas, no una regla individual): la clave weights, un peso entero mayor o igual a 0 por cada criterio de comparación (por ejemplo tipo de prenda, talla, etc., excluyendo proceso, subproceso y operación). El sistema usa estos pesos para elegir, entre varias reglas que podrían aplicar, la más específica: suma puntos por cada criterio coincidente; en empate gana la de alcance más preciso. También define qué criterios se pueden relacionar al crear reglas. Se RECHAZA si el id no es el del ítem de reglas, si un peso no es entero no negativo o si la clave no es un criterio válido. Solo se reemplaza weights; otras claves se conservan. Confirma los pesos con la persona. Es reversible reenviando los anteriores.';
}
