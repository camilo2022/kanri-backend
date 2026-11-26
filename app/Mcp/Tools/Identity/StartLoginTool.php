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

#[Name('start_login')]
#[Title('Iniciar sesión en Kanri')]
#[Description('Empieza el inicio de sesión de la persona: devuelve un enlace a la página de Kanri donde ella escribe su usuario y su clave (la clave nunca se escribe en el chat) y un código. Entrégale el enlace, espera a que diga que ya inició sesión y llama a finish_login con el código. No pidas ni uses su clave.')]
#[IsDestructive(false)]
class StartLoginTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            app(ActorResolver::class)->resolve(null);
        } catch (McpFailure $failure) {
            return $failure->response();
        }

        $ticket = app(LoginTicket::class)->start();

        return Response::json([
            'ok' => true,
            'url' => $ticket['url'],
            'code' => $ticket['code'],
            'expires_in_minutes' => $ticket['expires_in_minutes'],
            'message' => 'Entrégale el enlace a la persona para que inicie sesión en Kanri. Cuando te avise que terminó, llama a finish_login con este código.',
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
