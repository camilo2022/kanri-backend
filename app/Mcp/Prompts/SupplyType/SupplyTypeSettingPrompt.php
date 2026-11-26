<?php

namespace App\Mcp\Prompts\SupplyType;

use App\Mcp\Prompts\RoutePrompt;

class SupplyTypeSettingPrompt extends RoutePrompt
{
    protected string $name = 'supply_type_setting';

    protected string $title = 'Configurar el formulario de los insumos de un tipo';

    protected string $description = 'Configurar el formulario de los insumos de un tipo';

    protected string $method = 'PUT';

    protected string $path = '/typification/supply_types/setting/{id}';

    protected string $business = 'Guarda el formulario que se pedirá al registrar insumos de este tipo (settings.form): una lista de campos, cada uno con field (nombre técnico en minúsculas con guiones bajos), label (etiqueta en MAYÚSCULAS), type y reglas. El sistema construye con esto la pantalla de captura y la validación de los insumos. Se RECHAZA si el nombre o la etiqueta de un campo se repite, si los números min/max no son válidos o no son coherentes, si una regex o fecha es inválida, si un select no trae opciones, si un campo dinámico no trae su modelo, o si un campo asociado no apunta a otro campo de varios valores. Reemplaza todo el formulario previo; el resto de settings se conserva, salvo has_variants, que se elimina. Confirma el formulario completo con la persona. Es reversible reenviando el anterior.';
}
