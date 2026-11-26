<?php

namespace App\Mcp\Tools\Routes;

use App\Mcp\ActorResolver;
use App\Mcp\McpFailure;
use App\Mcp\Support\RouteCatalog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('search_routes')]
#[Title('Buscar rutas de Kanri')]
#[Description('Busca las rutas del API de Kanri por texto (en español o en inglés) o por grupo, y las describe: método, ruta, resumen, permisos que exige y, cuando hay pocas coincidencias, los campos con sus reglas y la explicación de negocio de la acción. Úsala antes de read_data para saber qué ruta llamar y con qué datos. Sin texto ni grupo devuelve los grupos disponibles. No consulta datos del negocio.')]
#[IsReadOnly]
#[IsIdempotent]
class SearchRoutesTool extends Tool
{
    private const LIMIT = 25;

    private const DETAIL_LIMIT = 3;

    public function handle(Request $request): Response
    {
        try {
            app(ActorResolver::class)->resolve($request->get('ai_key'));
        } catch (McpFailure $failure) {
            return $failure->response();
        }

        $catalog = app(RouteCatalog::class);
        $query = Str::lower(trim((string) $request->get('query', '')));
        $group = Str::lower(trim((string) $request->get('group', '')));
        $method = strtoupper(trim((string) $request->get('method', '')));

        if ($query === '' && $group === '' && $method === '') {
            return Response::json([
                'ok' => true,
                'groups' => $catalog->groups(),
                'message' => 'Estos son los grupos de rutas. Vuelve a llamar con query, group o method para ver las rutas.',
            ]);
        }

        $words = array_filter(explode(' ', $query));

        $matches = $catalog->entries()
            ->when($group !== '', fn ($entries) => $entries->filter(fn ($entry) => Str::lower($entry['group']) === $group))
            ->when($method !== '', fn ($entries) => $entries->filter(fn ($entry) => $entry['method'] === $method))
            ->filter(function ($entry) use ($words) {
                $haystack = Str::lower($entry['method'].' '.$entry['path'].' '.$entry['group'].' '.$entry['summary']);

                return collect($words)->every(fn ($word) => str_contains($haystack, $word));
            })
            ->values();

        $detailed = $matches->count() <= self::DETAIL_LIMIT;

        $routes = $matches->take(self::LIMIT)->map(function ($entry) use ($catalog, $detailed) {
            $described = $catalog->describe($entry);

            $route = [
                'method' => $entry['method'],
                'path' => $entry['path'],
                'summary' => $entry['summary'],
                'group' => $entry['group'],
                'usable_with' => $entry['usable'] ?? 'not_available_yet',
                'permissions' => $described['permissions'],
                'prompt' => $described['prompt'],
            ];

            if ($detailed) {
                $route['fields'] = $described['fields'];
                $route['business'] = $described['business'] ?? $entry['description'];
            }

            return $route;
        })->all();

        return Response::json([
            'ok' => true,
            'total' => $matches->count(),
            'shown' => count($routes),
            'routes' => $routes,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Texto a buscar en la ruta o el resumen, por ejemplo «bancos», «banks» o «tipos de bota». Cada palabra debe aparecer.'),
            'group' => $schema->string()->description('Primer segmento de la ruta, por ejemplo typification, technical_sheets, movements o banks.'),
            'method' => $schema->string()->description('GET, POST, PUT, PATCH o DELETE.'),
            'ai_key' => $schema->string()->description('Llave de IA de la persona. Opcional.'),
        ];
    }
}
