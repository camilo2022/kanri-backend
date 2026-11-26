<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\WebLoginRequest;
use App\Mcp\ActorResolver;
use App\Mcp\Support\LoginTicket;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Página a la que lleva start_login: la persona escribe su usuario y clave en Kanri (nunca en el chat) y el chat
 * recoge su llave de IA con finish_login. No abre sesión web: solo deja la llave bajo el código del enlace.
 */
class AssistantLoginController extends Controller
{
    private const LOGIN_MAX_ATTEMPTS = 5;

    private const LOGIN_LOCK_SECONDS = 900;

    public function show(LoginTicket $ticket, string $code)
    {
        if (! $ticket->isPending($code)) {
            return view('assistant.login-expired');
        }

        return view('assistant.login', ['code' => $code]);
    }

    public function login(WebLoginRequest $request, LoginTicket $ticket, string $code)
    {
        if (! $ticket->isPending($code)) {
            return view('assistant.login-expired');
        }

        $username = ltrim(trim((string) $request->input('username')), '@');
        $ip_key = 'login_assistant_ip:'.$request->ip();
        $user_key = 'login_assistant_user:'.sha1(mb_strtolower($username));

        foreach ([$ip_key, $user_key] as $limiter_key) {
            if (RateLimiter::tooManyAttempts($limiter_key, self::LOGIN_MAX_ATTEMPTS)) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn($limiter_key) / 60));

                return back()->withInput($request->only('username'))->withErrors([
                    'username' => 'Demasiados intentos fallidos. Inténtalo de nuevo en '.$minutes.' minuto(s).',
                ]);
            }
        }

        $user = User::where('username', $username)->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($ip_key, self::LOGIN_LOCK_SECONDS);
            RateLimiter::hit($user_key, self::LOGIN_LOCK_SECONDS);

            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Usuario o contraseña incorrectos. Verifica tus datos e inténtalo de nuevo.',
            ]);
        }

        RateLimiter::clear($ip_key);
        RateLimiter::clear($user_key);

        if (! $user->hasAnyPermission([ActorResolver::PERMISSION])) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Tu usuario no tiene permiso para usar el asistente. Pide a un administrador de Kanri que te lo dé.',
            ]);
        }

        $user->tokens()->where('name', ActorResolver::KEY_NAME)->delete();

        $expires_at = now()->addHours(config('asistente.ia_token_duracion_max_horas'));
        $token = $user->createToken(ActorResolver::KEY_NAME, [ActorResolver::KEY_ABILITY], $expires_at);

        $ticket->complete($code, $token->plainTextToken);

        return view('assistant.login-done', ['username' => $user->username]);
    }
}
