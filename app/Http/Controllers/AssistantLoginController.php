<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\WebLoginRequest;
use App\Mcp\ActorResolver;
use App\Mcp\Support\LoginTicket;
use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Support\Facades\Hash;

/**
 * Página a la que lleva start_login: la persona escribe su usuario y clave en Kanri (nunca en el chat) y el chat
 * recoge su llave de IA con finish_login. No abre sesión web: solo deja la llave bajo el código del enlace.
 */
class AssistantLoginController extends Controller
{
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
        $throttle = new LoginThrottle('assistant');

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

        if (! $user->hasDirectPermission(ActorResolver::PERMISSION)) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Tu usuario no tiene permiso para usar el asistente. Pide a un administrador de Kanri que te lo dé.',
            ]);
        }

        $user->tokens()->where('name', ActorResolver::KEY_NAME)->delete();

        $expires_at = now()->addHours(config('assistant.ai_token_max_hours'));
        $token = $user->createToken(ActorResolver::KEY_NAME, [ActorResolver::KEY_ABILITY], $expires_at);

        $ticket->complete($code, $token->plainTextToken);

        return view('assistant.login-done', ['username' => $user->username]);
    }
}
