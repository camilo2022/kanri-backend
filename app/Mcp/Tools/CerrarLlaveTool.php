<?php

namespace App\Mcp\Tools;

use App\Mcp\ActorResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('cerrar_llave')]
#[Title('Cerrar la llave de IA')]
#[Description('Cierra la llave de IA de la persona cuando termina de trabajar con el asistente: la llave deja de servir. Se puede repetir sin efecto.')]
#[IsDestructive(false)]
#[IsIdempotent]
class CerrarLlaveTool extends Tool
{
    public function handle(Request $request): Response
    {
        $closed = app(ActorResolver::class)->close((string) $request->get('llave_ia'));

        return Response::json([
            'ok' => true,
            'cerrada' => $closed,
            'mensaje' => $closed ? 'Llave cerrada.' : 'Esa llave ya no estaba activa.',
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'llave_ia' => $schema->string()->description('La llave de IA que se quiere cerrar.')->required(),
        ];
    }
}
