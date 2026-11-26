<?php

namespace App\Mcp;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Decide a nombre de quién actúa el asistente. Lo usan todas las herramientas.
 *
 * - Sin llave de IA: actúa el usuario que conectó el conector (modo connector).
 * - Con llave de IA: actúa su dueño (modo ai_key). Si la llave no sirve se rechaza; nunca se pasa en silencio al
 *   usuario del conector. La herramienta recibe la llave, jamás un user_id.
 */
class ActorResolver
{
    public const KEY_NAME = 'ai_token';

    public const KEY_ABILITY = 'assistant';

    public const PERMISSION = 'assistant.connect';

    /**
     * @return array{user: User, mode: string}
     */
    public function resolve(?string $assistant_key): array
    {
        if ($assistant_key === null || trim($assistant_key) === '') {
            $actor = auth('mcp')->user();
            $mode = 'connector';
        } else {
            $actor = $this->usableKey($assistant_key)->tokenable;
            $mode = 'ai_key';
        }

        if (! $actor instanceof User || $actor->trashed()) {
            throw McpFailure::expiredKey();
        }

        $this->requireConnectPermission($actor);

        auth('mcp')->setUser($actor);
        request()->attributes->set('mcp_mode', $mode);

        return ['user' => $actor, 'mode' => $mode];
    }

    public function close(string $assistant_key): bool
    {
        $token = PersonalAccessToken::findToken(trim($assistant_key));

        if (! $token || $token->name !== self::KEY_NAME) {
            return false;
        }

        $token->delete();

        return true;
    }

    private function requireConnectPermission(User $actor): void
    {
        try {
            app(PermissionService::class)->authorize($actor, [self::PERMISSION]);
        } catch (HttpResponseException $e) {
            $body = json_decode($e->getResponse()->getContent(), true) ?: [];

            throw McpFailure::forbidden([self::PERMISSION], $body['message'] ?? 'No tienes permiso para usar el asistente.');
        }
    }

    private function usableKey(string $assistant_key): PersonalAccessToken
    {
        $token = PersonalAccessToken::findToken(trim($assistant_key));

        $usable = $token
            && $token->name === self::KEY_NAME
            && $token->can(self::KEY_ABILITY)
            && (! $token->expires_at || $token->expires_at->isFuture())
            && (! $token->last_used_at || $token->last_used_at->gte(now()->subMinutes(config('assistant.ai_token_inactivity_minutes'))))
            && $token->tokenable;

        if (! $usable) {
            throw McpFailure::expiredKey();
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $token;
    }
}
