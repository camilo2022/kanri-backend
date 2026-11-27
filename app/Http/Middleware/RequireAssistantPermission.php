<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Solo los usuarios con el permiso assistant.connect (el mismo que habilita el chat con llave de IA) pueden autorizar
 * al asistente. Si todavía no hay sesión web no
 * se decide nada: la autorización pide primero el inicio de sesión y vuelve aquí.
 */
class RequireAssistantPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('web');

        if ($user && ! $user->hasDirectPermission('assistant.connect')) {
            return response()->view('oauth.denied', ['username' => $user->username], 403);
        }

        return $next($request);
    }
}
