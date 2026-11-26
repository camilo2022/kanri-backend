<?php

namespace App\Mcp\Prompts\Supply;

use App\Mcp\Prompts\RoutePrompt;

class SupplyUpdatePrompt extends RoutePrompt
{
    protected string $name = 'supply_update';

    protected string $title = 'Editar un insumo';

    protected string $description = 'Editar un insumo';

    protected string $method = 'PUT';

    protected string $path = '/typification/supply_types/supplies/update/{id}';

    protected string $business = 'Edita un insumo. El tipo no cambia: el formulario que se aplica es el del tipo que el insumo ya tiene. Se reemplazan el nombre, la descripción y los valores del formulario (por ello hay que enviar los campos completos), las relaciones con catálogos y las fotos (se pueden marcar archivos para eliminar, pero solo del propio insumo). Se rechaza si el nombre lo usa otro insumo, si un valor incumple las reglas del formulario del tipo o si una relación no pertenece al grupo configurado. Queda en la auditoría. La IA debe mostrar qué cambia y confirmar. Los datos son reversibles al editar; las fotos eliminadas o reemplazadas pueden no recuperarse.';
}
