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
        parent::__construct($payload['mensaje'] ?? $payload['tipo']);
    }

    public static function expiredKey(): self
    {
        return new self([
            'ok' => false,
            'tipo' => 'llave_vencida',
            'mensaje' => 'Tu llave de IA venció o no es válida. Genera una nueva en Kanri.',
        ]);
    }

    public static function forbidden(array $permissions, string $message): self
    {
        return new self([
            'ok' => false,
            'tipo' => 'permiso',
            'permiso' => $permissions,
            'mensaje' => $message,
        ]);
    }

    public static function invalid(array $errors): self
    {
        return new self([
            'ok' => false,
            'tipo' => 'validacion',
            'errores' => $errors,
        ]);
    }

    public static function readOnly(): self
    {
        return new self([
            'ok' => false,
            'tipo' => 'solo_lectura',
            'mensaje' => 'El asistente está en modo solo lectura: por ahora no puede guardar cambios.',
        ]);
    }

    public function response(): Response
    {
        return Response::error(json_encode($this->payload, JSON_UNESCAPED_UNICODE));
    }
}
