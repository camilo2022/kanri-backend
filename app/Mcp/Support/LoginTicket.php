<?php

namespace App\Mcp\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Puente entre el chat y la página de inicio de sesión de Kanri. start_login crea un código; la persona inicia
 * sesión en el enlace y la página deja la llave cifrada bajo ese código; finish_login la recoge una sola vez. Se
 * guarda en caché, con vencimiento, y solo el hash del código forma parte de la clave.
 */
class LoginTicket
{
    /**
     * @return array{code: string, url: string, expires_in_minutes: int}
     */
    public function start(): array
    {
        $code = Str::random(48);
        $minutes = (int) config('asistente.login_minutes', 10);

        Cache::put($this->cacheKey($code), ['status' => 'pending'], now()->addMinutes($minutes));

        return [
            'code' => $code,
            'url' => route('assistant.login.show', ['code' => $code]),
            'expires_in_minutes' => $minutes,
        ];
    }

    public function isPending(string $code): bool
    {
        return (Cache::get($this->cacheKey($code))['status'] ?? null) === 'pending';
    }

    public function complete(string $code, string $plain_token): void
    {
        Cache::put(
            $this->cacheKey($code),
            ['status' => 'ready', 'token' => Crypt::encryptString($plain_token)],
            now()->addMinutes((int) config('asistente.login_minutes', 10))
        );
    }

    /**
     * @return array{status: string, token?: string}|null null si el código no existe, ya se usó o venció
     */
    public function claim(string $code): ?array
    {
        $ticket = Cache::get($this->cacheKey($code));

        if (! $ticket) {
            return null;
        }

        if ($ticket['status'] === 'pending') {
            return ['status' => 'pending'];
        }

        $ticket = Cache::pull($this->cacheKey($code));

        if (! $ticket) {
            return null;
        }

        return ['status' => 'ready', 'token' => Crypt::decryptString($ticket['token'])];
    }

    private function cacheKey(string $code): string
    {
        return 'assistant_login:'.hash('sha256', trim($code));
    }
}
