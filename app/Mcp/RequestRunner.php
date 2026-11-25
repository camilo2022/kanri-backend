<?php

namespace App\Mcp;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

/**
 * Único camino para que una herramienta use un FormRequest de Kanri: arma el Request con los datos y el actor, aplica
 * comprueba el permiso real del actor (el de la persona dueña de la llave) y valida con las mismas reglas de la API.
 */
class RequestRunner
{
    public function run(string $request_class, array $data, User $actor, array $route_parameters = [], bool $writes = false): FormRequest
    {
        if ($writes && config('asistente.solo_lectura')) {
            throw McpFailure::readOnly();
        }

        $base = Request::create('/mcp', 'POST', $data, [], [], ['HTTP_ACCEPT' => 'application/json']);
        $route = (new Route(['POST'], 'mcp', []))->bind($base);
        foreach ($route_parameters as $name => $value) {
            $route->setParameter($name, $value);
        }
        $base->setRouteResolver(fn () => $route);

        $request = $request_class::createFrom($base);
        $request->setContainer(app())->setRedirector(app('redirect'))->setUserResolver(fn () => $actor);

        $accepted = method_exists($request, 'permissions') ? $request->permissions() : [];

        try {
            app(PermissionService::class)->authorize($actor, $accepted);
            $request->validateResolved();
        } catch (HttpResponseException $e) {
            $body = json_decode($e->getResponse()->getContent(), true) ?: [];

            if ($e->getResponse()->getStatusCode() === 403) {
                throw McpFailure::forbidden($accepted, $body['message'] ?? 'No tienes permiso para realizar esta acción.');
            }

            throw McpFailure::invalid($body['errors'] ?? ['mensaje' => $body['message'] ?? 'Datos inválidos.']);
        } catch (ValidationException $e) {
            throw McpFailure::invalid($e->errors());
        }

        return $request;
    }
}
