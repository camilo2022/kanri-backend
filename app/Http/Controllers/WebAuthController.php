<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\WebLoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Inicio de sesión web (guard web, con cookie). Solo lo usa la pantalla de autorización del asistente: el resto de
 * Kanri entra por la API con Sanctum.
 */
class WebAuthController extends Controller
{
    private const LOGIN_MAX_ATTEMPTS = 5;

    private const LOGIN_LOCK_SECONDS = 900;

    public function show()
    {
        return view('login');
    }

    public function login(WebLoginRequest $request)
    {
        $username = ltrim(trim((string) $request->input('username')), '@');
        $ip_key = 'login_web_ip:' . $request->ip();
        $user_key = 'login_web_user:' . sha1(mb_strtolower($username));

        foreach ([$ip_key, $user_key] as $limiter_key) {
            if (RateLimiter::tooManyAttempts($limiter_key, self::LOGIN_MAX_ATTEMPTS)) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn($limiter_key) / 60));

                return back()->withInput($request->only('username'))->withErrors([
                    'username' => 'Demasiados intentos fallidos. Inténtalo de nuevo en ' . $minutes . ' minuto(s).',
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

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}
