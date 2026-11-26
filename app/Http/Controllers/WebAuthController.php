<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\WebLoginRequest;
use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Inicio de sesión web (guard web, con cookie). Solo lo usa la pantalla de autorización del asistente: el resto de
 * Kanri entra por la API con Sanctum.
 */
class WebAuthController extends Controller
{
    public function show()
    {
        return view('login');
    }

    public function login(WebLoginRequest $request)
    {
        $username = ltrim(trim((string) $request->input('username')), '@');
        $throttle = new LoginThrottle('web');

        if ($block = $throttle->blocked($username, $request->ip())) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => $block['message'].' '.$block['detail'],
            ]);
        }

        $user = User::where('username', $username)->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            $throttle->fail($username, $request->ip());

            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Usuario o contraseña incorrectos. Verifica tus datos e inténtalo de nuevo.',
            ]);
        }

        $throttle->succeed($username, $request->ip());

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}
