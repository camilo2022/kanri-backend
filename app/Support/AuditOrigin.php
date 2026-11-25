<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Desde dónde se hizo una acción auditada. La IA (MCP) se detecta en el servidor y no se puede declarar desde fuera;
 * web y móvil lo dicen en la cabecera X-Kanri-Client de cada petición.
 */
class AuditOrigin
{
    public const HEADER = 'X-Kanri-Client';

    private const CLIENTS = ['web', 'mobile'];

    public static function current(): string
    {
        $request = request();

        if (! $request->route()) {
            return 'system';
        }

        if ($request->is('mcp', 'mcp/*') || Auth::guard('mcp')->hasUser()) {
            return 'mcp';
        }

        $client = strtolower((string) $request->header(self::HEADER));

        return in_array($client, self::CLIENTS, true) ? $client : 'api';
    }
}
