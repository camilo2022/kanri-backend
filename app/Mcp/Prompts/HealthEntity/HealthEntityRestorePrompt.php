<?php

namespace App\Mcp\Prompts\HealthEntity;

use App\Mcp\Prompts\RoutePrompt;

class HealthEntityRestorePrompt extends RoutePrompt
{
    protected string $name = 'health_entity_restore';

    protected string $title = 'Restaurar una entidad de salud desactivada';

    protected string $description = 'Restaurar una entidad de salud desactivada';

    protected string $method = 'PATCH';

    protected string $path = '/health_entities/restore/{id}';

    protected string $business = 'Reactiva un registro de health entity que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un health entity. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.';
}
