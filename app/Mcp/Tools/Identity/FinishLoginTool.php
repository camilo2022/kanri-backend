<?php

namespace App\Mcp\Tools\Identity;

use App\Mcp\ActorResolver;
use App\Mcp\McpFailure;
use App\Mcp\Support\LoginTicket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Sanctum\PersonalAccessToken;

#[Name('finish_login')]
#[Title('Terminar el inicio de sesión')]
#[Description('Recoge la llave de IA de la persona después de que inició sesión en el enlace de start_login. La llave se entrega una sola vez. Guárdala y envíala como ai_key en cada herramienta; no la muestres en la conversación. Si responde login_pending, la persona aún no inicia sesión.')]
#[IsDestructive(false)]
class FinishLoginTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            app(ActorResolver::class)->resolve(null);

            $claim = app(LoginTicket::class)->claim((string) $request->get('code'));

            if ($claim === null) {
                throw McpFailure::loginExpired();
            }

            if ($claim['status'] === 'pending') {
                throw McpFailure::loginPending();
            }
        } catch (McpFailure $failure) {
            return $failure->response();
        }

        $user = PersonalAccessToken::findToken($claim['token'])?->tokenable;

        return Response::json([
            'ok' => true,
            'user' => $user?->username,
            'ai_key' => $claim['token'],
            'inactivity_minutes' => config('assistant.ai_token_inactivity_minutes'),
            'message' => 'Sesión iniciada. Envía esta llave como ai_key en cada herramienta y no la muestres en la conversación.',
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('El código que devolvió start_login.')->required(),
        ];
    }
}
