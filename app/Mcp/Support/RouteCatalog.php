<?php

namespace App\Mcp\Support;

use App\Mcp\McpFailure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Conoce las rutas del API que el asistente puede usar: las lee del router, saca los permisos y las reglas del
 * FormRequest real de cada una y toma el resumen del Swagger y el texto de negocio del prompt de la ruta.
 */
class RouteCatalog
{
    private ?Collection $entries = null;

    private ?array $swagger = null;

    private ?array $prompts = null;

    public function __construct(private RouteGuard $guard) {}

    public function entries(): Collection
    {
        return $this->entries ??= collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route) => str_starts_with($route->uri(), 'api/') && ! $this->guard->isExcluded($route->uri()))
            ->flatMap(fn (LaravelRoute $route) => collect($route->methods())
                ->reject(fn (string $method) => $method === 'HEAD')
                ->map(fn (string $method) => $this->entry($route, $method)))
            ->values();
    }

    public function groups(): array
    {
        return $this->entries()->countBy('group')->sortKeys()->all();
    }

    public function route(string $method, string $path): LaravelRoute
    {
        try {
            return Route::getRoutes()->match(Request::create('/api'.'/'.ltrim($path, '/'), $method));
        } catch (HttpException) {
            throw McpFailure::notFound("No existe la ruta {$method} {$path}. Usa search_routes para buscar la correcta.");
        }
    }

    public function details(LaravelRoute $route): array
    {
        $request_class = $this->requestClass($route);
        $instance = $this->instance($request_class, $route);

        return [
            'request' => $request_class,
            'permissions' => $this->permissions($instance),
            'fields' => $this->fields($instance),
        ];
    }

    public function describe(array $entry): array
    {
        $found = $this->routeByUri($entry['method'], $entry['uri']);
        $details = $found ? $this->details($found) : ['permissions' => [], 'fields' => []];

        return array_merge($entry, [
            'permissions' => $details['permissions'],
            'fields' => $details['fields'],
            'prompt' => $this->promptName($entry['method'], $entry['path']),
            'business' => $this->business($entry['method'], $entry['path']),
        ]);
    }

    public function permissionsFor(LaravelRoute $route): array
    {
        return $this->permissions($this->instance($this->requestClass($route), $route));
    }

    public function promptClass(string $method, string $path): ?string
    {
        return $this->prompts()["{$method} {$path}"] ?? null;
    }

    public function prompts(): array
    {
        if ($this->prompts === null) {
            $file = app_path('Mcp/Prompts/prompts.php');
            $this->prompts = is_file($file) ? require $file : [];
        }

        return $this->prompts;
    }

    public function business(string $method, string $path): ?string
    {
        $class = $this->promptClass($method, $path);

        return $class ? app($class)->businessText() : null;
    }

    public function swaggerOperation(string $method, string $path): ?array
    {
        return $this->swagger()[$path][strtolower($method)] ?? null;
    }

    private function entry(LaravelRoute $route, string $method): array
    {
        $uri = $route->uri();
        $path = '/'.substr($uri, 4);
        $operation = $this->swaggerOperation($method, str_replace('?', '', $path));

        return [
            'method' => $method,
            'uri' => $uri,
            'path' => $path,
            'group' => explode('/', $uri)[1] ?? '',
            'action' => $route->getActionName(),
            'summary' => $operation['summary'] ?? null,
            'description' => $operation['description'] ?? null,
            'usable' => match (true) {
                $this->isBulk($route) => 'query_data',
                $this->guard->isReadable($method, $uri) => 'read_data',
                $this->guard->isWritable($method, $uri) => 'prepare_write',
                default => null,
            },
        ];
    }

    /**
     * La ruta de consulta de un registro (la «find» del mismo controlador) para una ruta de escritura que tiene un solo
     * parámetro en la ruta, o null si no hay.
     */
    public function findPath(LaravelRoute $route): ?string
    {
        $parameters = $route->parameters();

        if (count($parameters) !== 1) {
            return null;
        }

        [$controller] = explode('@', $route->getActionName());

        foreach (Route::getRoutes()->getRoutes() as $candidate) {
            $is_find = $candidate->getActionName() === $controller.'@find'
                && in_array('GET', $candidate->methods(), true)
                && count($candidate->parameterNames()) === 1
                && ! $this->guard->isExcluded($candidate->uri());

            if ($is_find) {
                return '/'.substr(preg_replace('/\{\w+\??\}/', (string) head($parameters), $candidate->uri()), 4);
            }
        }

        return null;
    }

    /**
     * Las rutas `all` (listados) se consultan por SQL con query_data, no con read_data.
     */
    public function isBulk(LaravelRoute $route): bool
    {
        return str_ends_with($route->getActionName(), '@all');
    }

    public function routeByUri(string $method, string $uri): ?LaravelRoute
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }

        return null;
    }

    private function requestClass(LaravelRoute $route): ?string
    {
        $action = $route->getActionName();

        if (! str_contains($action, '@')) {
            return null;
        }

        [$controller, $method] = explode('@', $action);

        if (! method_exists($controller, $method)) {
            return null;
        }

        foreach ((new ReflectionMethod($controller, $method))->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && is_subclass_of($type->getName(), FormRequest::class)) {
                return $type->getName();
            }
        }

        return null;
    }

    private function instance(?string $class, ?LaravelRoute $route = null): ?FormRequest
    {
        if (! $class) {
            return null;
        }

        try {
            // Con la ruta, permissions() puede leer su defaults (el historial pide el permiso según el modelo)
            return (new $class)->setRouteResolver(fn () => $route);
        } catch (Throwable) {
            return null;
        }
    }

    private function permissions(?FormRequest $instance): array
    {
        if (! $instance || ! method_exists($instance, 'permissions')) {
            return [];
        }

        try {
            return array_values($instance->permissions());
        } catch (Throwable) {
            return [];
        }
    }

    private function fields(?FormRequest $instance): array
    {
        if (! $instance) {
            return [];
        }

        try {
            $rules = $instance->rules();
        } catch (Throwable) {
            return [];
        }

        $fields = [];

        foreach ($rules as $field => $rule) {
            $parts = is_array($rule) ? $rule : explode('|', (string) $rule);
            $fields[$field] = implode('|', array_filter($parts, 'is_string'));
        }

        return $fields;
    }

    private function promptName(string $method, string $path): ?string
    {
        $class = $this->promptClass($method, $path);

        return $class ? app($class)->name() : null;
    }

    private function swagger(): array
    {
        if ($this->swagger === null) {
            $file = storage_path('api-docs/api-docs.json');
            $this->swagger = is_file($file) ? (json_decode(file_get_contents($file), true)['paths'] ?? []) : [];
        }

        return $this->swagger;
    }
}
