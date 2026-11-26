<?php

namespace App\Mcp\Support;

use Illuminate\Support\Str;

/**
 * Decide qué rutas del API puede usar el asistente. La lista de rutas excluidas y la de rutas POST que solo leen
 * viven en config/assistant.php.
 */
class RouteGuard
{
    public function isExcluded(string $uri): bool
    {
        return Str::is(config('assistant.excluded_routes', []), $uri);
    }

    public function isReadable(string $method, string $uri): bool
    {
        if ($this->isExcluded($uri)) {
            return false;
        }

        if ($method === 'GET') {
            return true;
        }

        return $method === 'POST' && Str::is(config('assistant.read_post_routes', []), $uri);
    }
}
