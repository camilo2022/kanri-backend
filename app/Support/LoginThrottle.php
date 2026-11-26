<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Límite de intentos fallidos de inicio de sesión. El bloqueo es por usuario (y por la red desde la que se intenta):
 * equivocarse con un usuario no bloquea a los demás. Aparte hay un tope amplio por red contra quien prueba muchos
 * usuarios distintos. Cada pantalla usa su propio ámbito para que sus contadores no se mezclen.
 */
class LoginThrottle
{
    private const USER_MAX_ATTEMPTS = 5;

    private const NETWORK_MAX_ATTEMPTS = 30;

    private const LOCK_SECONDS = 900;

    public function __construct(private string $scope) {}

    /**
     * @return array{message: string, detail: string, seconds: int}|null null si todavía puede intentar
     */
    public function blocked(string $username, string $ip): ?array
    {
        foreach ([
            [$this->userKey($username, $ip), self::USER_MAX_ATTEMPTS, 'Demasiados intentos fallidos con este usuario.'],
            [$this->networkKey($ip), self::NETWORK_MAX_ATTEMPTS, 'Demasiados intentos fallidos desde esta red.'],
        ] as [$key, $max, $message]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $seconds = RateLimiter::availableIn($key);

                return [
                    'message' => $message,
                    'detail' => 'Inténtalo de nuevo en '.max(1, (int) ceil($seconds / 60)).' minuto(s).',
                    'seconds' => $seconds,
                ];
            }
        }

        return null;
    }

    public function fail(string $username, string $ip): void
    {
        RateLimiter::hit($this->userKey($username, $ip), self::LOCK_SECONDS);
        RateLimiter::hit($this->networkKey($ip), self::LOCK_SECONDS);
    }

    public function succeed(string $username, string $ip): void
    {
        RateLimiter::clear($this->userKey($username, $ip));
    }

    private function userKey(string $username, string $ip): string
    {
        return 'login_'.$this->scope.'_user:'.sha1(mb_strtolower($username).'|'.$ip);
    }

    private function networkKey(string $ip): string
    {
        return 'login_'.$this->scope.'_network:'.$ip;
    }
}
