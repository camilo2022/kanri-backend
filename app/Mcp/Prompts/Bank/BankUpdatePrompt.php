<?php

namespace App\Mcp\Prompts\Bank;

use App\Mcp\Prompts\RoutePrompt;

class BankUpdatePrompt extends RoutePrompt
{
    protected string $name = 'bank_update';

    protected string $title = 'Editar un banco';

    protected string $description = 'Editar un banco';

    protected string $method = 'PUT';

    protected string $path = '/banks/update/{id}';

    protected string $business = 'Edita el nombre y la descripción de un banco existente. Se usa para corregir o renombrar la entidad. No toca las reglas de cuenta guardadas en settings. Se RECHAZA si el banco no existe, si nombre o descripción no vienen en MAYÚSCULAS (uppercase rechaza, no convierte), si el nombre tiene menos de 4 caracteres o si coincide con el de otro banco (activo o eliminado). El banco puede estar eliminado: la validación solo exige que exista. Antes de ejecutarlo, confirma qué banco se cambia y los nuevos textos. Es reversible volviendo a editarlo; el historial queda en la auditoría.';
}
