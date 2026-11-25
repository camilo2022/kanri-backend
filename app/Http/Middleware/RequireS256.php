<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Exige PKCE con el método S256 al iniciar la autorización de OAuth. Sin esto, un código de autorización robado
 * serviría sin el secreto que nunca pasa por el navegador, y el servidor de OAuth acepta el método plain por defecto.
 */
class RequireS256
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->filled('code_challenge') || $request->query('code_challenge_method') !== 'S256') {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Se requiere PKCE: envíe code_challenge y code_challenge_method=S256.',
            ], 400);
        }

        return $next($request);
    }
}
