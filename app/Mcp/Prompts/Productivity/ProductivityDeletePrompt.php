<?php

namespace App\Mcp\Prompts\Productivity;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDeletePrompt extends RoutePrompt
{
    protected string $name = 'productivity_delete';

    protected string $title = 'Desactivar un turno';

    protected string $description = 'Desactivar un turno';

    protected string $method = 'DELETE';

    protected string $path = '/productivity/delete/{id}';

    protected string $business = 'Desactiva (borrado lógico) un turno; se usa para anular un turno creado por error o que no se realizará. Se rechaza si el turno ya inició (En curso) o terminó, o si tiene cualquier programación o trabajo registrado: primero hay que quitar esos registros. Efecto: el turno queda con fecha de eliminación y deja de contar para cruces de horario; queda auditado. La IA debe confirmar proceso, fecha y horario del turno. Es reversible con la ruta de restaurar, siempre que no se cruce con otro turno activo del mismo proceso.';
}
