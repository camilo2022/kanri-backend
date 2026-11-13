<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Foundation\Configuration\Middleware;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        // Sin sesión web no hay pantalla de login a la que redirigir: siempre se responde 401 en JSON
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->api(append: [\App\Http\Middleware\RejectUnsafeInput::class]);
        $middleware->alias([
            'can' => \App\Http\Middleware\SpatieCheckRoleAndPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'No autenticado.',
                    'error' => 'No autenticado.'
                ], 401);
            }
        });

        // Mensajes en español para rutas o registros inexistentes y métodos no permitidos
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                $texto = $e instanceof ModelNotFoundException || $e->getPrevious() instanceof ModelNotFoundException
                    ? 'El registro solicitado no existe.'
                    : 'La ruta solicitada no existe.';

                return response()->json(['message' => $texto, 'error' => $texto], 404);
            }
        });
        $exceptions->render(function (InvalidSignatureException $e, $request) {
            if ($request->is('api/*')) {
                $texto = 'El enlace no es válido.';

                return response()->json(['message' => $texto, 'error' => $texto], 403);
            }
        });
        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                $texto = 'El método no está permitido para esta ruta.';

                return response()->json(['message' => $texto, 'error' => $texto], 405, ['Allow' => $e->getHeaders()['Allow'] ?? '']);
            }
        });

        // Lo que ningún controlador atrapó tampoco muestra detalles; Laravel lo deja en el log
        $exceptions->render(function (\Throwable $e, $request) {
            if (! $request->is('api/*') || $e instanceof HttpResponseException || $e instanceof ValidationException) {
                return null;
            }

            $http = $e instanceof HttpExceptionInterface;
            $status = $http ? $e->getStatusCode() : 500;
            $texto = match (true) {
                $status === 403 => 'No tienes permiso para realizar esta acción.',
                $http && $status < 500 && $e->getMessage() !== '' => $e->getMessage(),
                default => 'Se ha producido un error inesperado, por favor, inténtelo de nuevo más tarde.',
            };

            return response()->json(['message' => $texto, 'error' => ['message' => $texto]], $status, $http ? $e->getHeaders() : []);
        });
    })->withProviders([
        App\Providers\EventServiceProvider::class,
    ])->create();
