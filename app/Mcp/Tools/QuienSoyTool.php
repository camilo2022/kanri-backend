<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('quien_soy')]
#[Title('Quién soy')]
#[Description('Dice con qué usuario de Kanri está trabajando el asistente en este momento y en qué modo. Solo consulta, no cambia nada.')]
#[IsReadOnly]
class QuienSoyTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user('mcp');

        return Response::json([
            'ok' => true,
            'usuario' => $user->username,
            'modo' => 'conector',
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
