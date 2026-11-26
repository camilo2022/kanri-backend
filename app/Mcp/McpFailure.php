<?php

namespace App\Mcp;

use Laravel\Mcp\Response;
use RuntimeException;

/**
 * Un fallo esperado de una herramienta del asistente. Lleva la respuesta por campos (nunca texto libre para
 * interpretar) y el asistente la recibe marcada como error.
 */
class McpFailure extends RuntimeException
{
    public function __construct(public readonly array $payload)
    {
        parent::__construct($payload['message'] ?? $payload['type']);
    }

    public static function expiredKey(): self
    {
        return new self([
            'ok' => false,
            'type' => 'key_expired',
            'message' => 'Tu llave de IA venció o no es válida. Genera una nueva en Kanri.',
        ]);
    }

    public static function loginPending(): self
    {
        return new self([
            'ok' => false,
            'type' => 'login_pending',
            'message' => 'La persona aún no inicia sesión en la página de Kanri. Pídele que abra el enlace, inicie sesión y te avise.',
        ]);
    }

    public static function loginExpired(): self
    {
        return new self([
            'ok' => false,
            'type' => 'login_expired',
            'message' => 'El enlace de inicio de sesión ya se usó o venció. Llama de nuevo a start_login.',
        ]);
    }

    public static function forbidden(array $permissions, string $message): self
    {
        return new self([
            'ok' => false,
            'type' => 'permission',
            'permission' => $permissions,
            'message' => $message,
        ]);
    }

    public static function invalid(array $errors, ?string $message = null): self
    {
        return new self(array_filter([
            'ok' => false,
            'type' => 'validation',
            'message' => $message,
            'errors' => $errors,
        ], fn ($value) => $value !== null));
    }

    public static function readOnly(): self
    {
        return new self([
            'ok' => false,
            'type' => 'read_only',
            'message' => 'El asistente está en modo solo lectura: por ahora no puede guardar cambios.',
        ]);
    }

    public static function invalidCode(): self
    {
        return new self([
            'ok' => false,
            'type' => 'confirmation_invalid',
            'message' => 'El código de confirmación no sirve: venció, ya se usó o no es de esta persona. Vuelve a llamar a prepare_write y muestra la vista previa de nuevo.',
        ]);
    }

    public static function tableNotAllowed(string $table, string $message): self
    {
        return new self([
            'ok' => false,
            'type' => 'table_not_allowed',
            'table' => $table,
            'message' => $message,
        ]);
    }

    public static function invalidSql(string $message): self
    {
        return new self([
            'ok' => false,
            'type' => 'invalid_sql',
            'message' => $message,
        ]);
    }

    public static function notFound(string $message): self
    {
        return new self([
            'ok' => false,
            'type' => 'not_found',
            'message' => $message,
        ]);
    }

    public static function routeNotAllowed(string $message): self
    {
        return new self([
            'ok' => false,
            'type' => 'route_not_allowed',
            'message' => $message,
        ]);
    }

    public static function tooLarge(array $details): self
    {
        return new self(array_merge([
            'ok' => false,
            'type' => 'too_large',
            'message' => 'La respuesta es demasiado grande. Pide menos datos: usa per_page y page, search o un filtro.',
        ], $details));
    }

    public static function server(string $message): self
    {
        return new self([
            'ok' => false,
            'type' => 'server_error',
            'message' => $message,
        ]);
    }

    public function response(): Response
    {
        return Response::error(json_encode($this->payload, JSON_UNESCAPED_UNICODE));
    }
}
