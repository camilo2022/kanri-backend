<?php

namespace App\Mcp\Prompts\HealthEntity;

use App\Mcp\Prompts\RoutePrompt;

class HealthEntityDeletePrompt extends RoutePrompt
{
    protected string $name = 'health_entity_delete';

    protected string $title = 'Desactivar una entidad de salud';

    protected string $description = 'Desactivar una entidad de salud';

    protected string $method = 'DELETE';

    protected string $path = '/health_entities/delete/{id}';

    protected string $business = 'Elimina un registro de health entity, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un health entity. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.

Notas: Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.';
}
