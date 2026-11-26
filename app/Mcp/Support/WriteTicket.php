<?php

namespace App\Mcp\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Código de confirmación de una escritura. prepare_write lo crea con lo que se va a enviar (persona, método, ruta y
 * datos); el código lleva una firma HMAC de su id y de la persona, y los datos se guardan en caché con vencimiento. Así
 * la IA no puede cambiar los datos después de que la persona dijo «sí», ni usar el código de otra persona.
 */
class WriteTicket
{
    /**
     * @return array{code: string, expires_in_minutes: int}
     */
    public function issue(User $actor, string $method, string $path, array $data): array
    {
        $id = Str::random(40);
        $minutes = (int) config('assistant.confirmation_minutes', 10);

        Cache::put($this->cacheKey($id), [
            'user_id' => $actor->id,
            'method' => $method,
            'path' => $path,
            'data' => $data,
        ], now()->addMinutes($minutes));

        return ['code' => $id.'.'.$this->signature($id, $actor->id), 'expires_in_minutes' => $minutes];
    }

    /**
     * Recoge los datos del código, una sola vez. Devuelve null si la firma no es de esta persona, si venció o si ya se
     * usó. Con la firma de otra persona el código no se gasta.
     *
     * @return array{user_id: int, method: string, path: string, data: array}|null
     */
    public function claim(string $code, User $actor): ?array
    {
        [$id, $signature] = array_pad(explode('.', trim($code), 2), 2, '');

        if ($id === '' || ! hash_equals($this->signature($id, $actor->id), $signature)) {
            return null;
        }

        if (! Cache::add($this->cacheKey($id).':used', true, now()->addMinutes((int) config('assistant.confirmation_minutes', 10) + 1))) {
            return null;
        }

        $ticket = Cache::pull($this->cacheKey($id));

        return $ticket && $ticket['user_id'] === $actor->id ? $ticket : null;
    }

    private function signature(string $id, int $user_id): string
    {
        return hash_hmac('sha256', $id.'|'.$user_id, (string) config('app.key'));
    }

    private function cacheKey(string $id): string
    {
        return 'assistant_write:'.hash('sha256', $id);
    }
}
