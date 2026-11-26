<?php

namespace App\Mcp\Support;

use App\Mcp\McpFailure;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ejecuta una ruta del API a nombre de la persona que actúa, como si la llamara el frontend: pasa por el mismo router,
 * el mismo FormRequest (reglas y permisos), el mismo controlador y la misma auditoría. No reescribe ninguna validación.
 */
class RouteGateway
{
    public function __construct(private RouteCatalog $catalog, private RouteGuard $guard) {}

    /**
     * Ejecuta una ruta de lectura y devuelve lo mismo que recibe el frontend. Si la ruta responde con un error, lanza
     * un McpFailure con el tipo que corresponde.
     */
    public function read(User $actor, string $method, string $path, array $parameters = []): array
    {
        $method = strtoupper($method);
        $route = $this->catalog->route($method, $path);

        if (! $this->guard->isReadable($method, $route->uri())) {
            throw McpFailure::routeNotAllowed(
                "La ruta {$method} {$path} no se puede consultar con read_data. Usa search_routes para ver las rutas disponibles."
            );
        }

        $response = $this->send($actor, $method, $path, $parameters);
        $body = $this->body($response);
        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'status' => $status] + $body;
        }

        $permissions = $this->catalog->permissionsFor($route);

        throw match (true) {
            $status === 401 => McpFailure::expiredKey(),
            $status === 403 => McpFailure::forbidden($permissions, $this->message($body, 'No tienes permiso para realizar esta acción.')),
            $status === 404 => McpFailure::notFound($this->message($body, 'No se encontró lo que pides.')),
            $status === 422 => McpFailure::invalid($body['errors'] ?? [], $body['message'] ?? null),
            $status === 429 => McpFailure::server('Se hicieron demasiadas peticiones. Espera un momento y vuelve a intentar.'),
            default => McpFailure::server('Kanri no pudo completar la consulta.'),
        };
    }

    private function send(User $actor, string $method, string $path, array $parameters)
    {
        $request = Request::create('/api/'.ltrim($path, '/'), $method, $parameters, [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KANRI_CLIENT' => 'mcp',
        ]);

        $original = app('request');

        try {
            Auth::guard('sanctum')->setUser($actor);
            Auth::shouldUse('sanctum');

            return app(Kernel::class)->handle($request);
        } finally {
            app()->instance('request', $original);
            Facade::clearResolvedInstance('request');
        }
    }

    private function body($response): array
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            throw McpFailure::routeNotAllowed('Esa ruta devuelve un archivo, y el asistente no puede manejarlo.');
        }

        $content = (string) $response->getContent();

        if (strlen($content) > config('asistente.max_response_chars')) {
            throw McpFailure::tooLarge([
                'size' => strlen($content),
                'limit' => config('asistente.max_response_chars'),
                'pagination' => json_decode($content, true)['data']['meta']['pagination'] ?? null,
            ]);
        }

        $body = json_decode($content, true);

        return is_array($body) ? $body : ['data' => $content];
    }

    private function message(array $body, string $default): string
    {
        $message = $body['error']['message'] ?? $body['message'] ?? $default;

        return is_string($message) ? $message : $default;
    }
}
