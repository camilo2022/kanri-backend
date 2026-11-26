<?php

namespace App\Mcp\Support;

use App\Mcp\McpFailure;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
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

    /**
     * Prepara una escritura sin guardar nada: comprueba el permiso y las reglas con el FormRequest real de la ruta (sin
     * llegar al controlador), trae el registro actual cuando hay uno y deja un código de confirmación.
     */
    public function prepare(User $actor, string $method, string $path, array $data = []): array
    {
        $method = strtoupper($method);
        $route = $this->catalog->route($method, $path);

        if (! $this->guard->isWritable($method, $route->uri())) {
            throw McpFailure::routeNotAllowed(
                "La ruta {$method} {$path} no se puede escribir desde el asistente. Usa search_routes para ver las rutas disponibles."
            );
        }

        $details = $this->catalog->details($route);

        if (! $details['request']) {
            throw McpFailure::routeNotAllowed("La ruta {$method} {$path} no tiene validación propia y el asistente no la puede preparar.");
        }

        $this->validate($actor, $route, $details['request'], $method, $path, $data);

        $entry = $this->catalog->entries()->first(fn (array $entry) => $entry['method'] === $method && $entry['uri'] === $route->uri());

        return [
            'ok' => true,
            'executed' => false,
            'method' => $method,
            'path' => $path,
            'summary' => $entry['summary'] ?? null,
            'permissions' => $details['permissions'],
            'data' => $data,
            'current' => $this->current($actor, $route),
        ] + app(WriteTicket::class)->issue($actor, $method, $path, $data);
    }

    private function validate(User $actor, $route, string $class, string $method, string $path, array $data): void
    {
        $base = Request::create('/api/'.ltrim($path, '/'), $method, $data, [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_KANRI_CLIENT' => 'mcp',
        ]);

        $base->setUserResolver(fn () => $actor);
        $route->bind($base);
        $base->setRouteResolver(fn () => $route);

        $form = $class::createFrom($base);
        $form->setContainer(app())->setRedirector(app(Redirector::class));

        $original = app('request');

        try {
            Auth::guard('sanctum')->setUser($actor);
            Auth::shouldUse('sanctum');
            app()->instance('request', $form);

            $form->validateResolved();
        } catch (HttpResponseException $e) {
            $status = $e->getResponse()->getStatusCode();
            $body = json_decode((string) $e->getResponse()->getContent(), true) ?: [];

            throw match (true) {
                $status === 401 => McpFailure::expiredKey(),
                $status === 403 => McpFailure::forbidden($this->catalog->permissionsFor($route), $this->message($body, 'No tienes permiso para realizar esta acción.')),
                $status === 422 => McpFailure::invalid($body['errors'] ?? [], $body['message'] ?? null),
                default => McpFailure::server('Kanri no pudo preparar la acción.'),
            };
        } finally {
            app()->instance('request', $original);
            Facade::clearResolvedInstance('request');
        }
    }

    /**
     * El registro tal como está hoy, para mostrarlo junto a lo que cambiaría. Si la persona no puede consultarlo o es
     * muy grande, no se incluye.
     */
    private function current(User $actor, $route): ?array
    {
        $find = $this->catalog->findPath($route);

        if (! $find) {
            return null;
        }

        try {
            $current = $this->read($actor, 'GET', $find);
        } catch (McpFailure) {
            return null;
        }

        return strlen(json_encode($current['data'] ?? null)) <= 20000 ? ($current['data'] ?? null) : null;
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
            return [
                'file' => $this->file($response),
                'message' => 'El archivo está listo. Entrégale a la persona el enlace file.url: lo descarga con su navegador y vence en '.config('assistant.file_minutes').' minutos. El archivo no se puede abrir desde el chat.',
            ];
        }

        $content = (string) $response->getContent();

        if (strlen($content) > config('assistant.max_response_chars')) {
            throw McpFailure::tooLarge([
                'size' => strlen($content),
                'limit' => config('assistant.max_response_chars'),
                'pagination' => json_decode($content, true)['data']['meta']['pagination'] ?? null,
            ]);
        }

        $body = json_decode($content, true);

        return is_array($body) ? $body : ['data' => $content];
    }

    private function file($response): array
    {
        if ($response instanceof BinaryFileResponse) {
            $content = (string) file_get_contents($response->getFile()->getPathname());
        } else {
            ob_start();
            $response->sendContent();
            $content = (string) ob_get_clean();
        }

        preg_match('/filename="?([^";]+)"?/', (string) $response->headers->get('Content-Disposition'), $match);

        $name = $match[1] ?? 'archivo';
        $mime = $response->headers->get('Content-Type') ?: 'application/octet-stream';

        return app(FileLink::class)->store($content, $name, $mime);
    }

    private function message(array $body, string $default): string
    {
        $message = $body['error']['message'] ?? $body['message'] ?? $default;

        return is_string($message) ? $message : $default;
    }
}
