<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Module;
use App\Models\Submodule;
use App\Traits\ApiResponser;
use App\Traits\ApiMessage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Endpoints de autenticación"
 * )
 *
 * @OA\PathItem(
 *     path="/auth",
 *     description="Rutas de autenticación"
 * )
 */
class AuthController extends Controller
{
    use ApiResponser, ApiMessage;

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Auth"},
     *     summary="Iniciar sesión de un usuario",
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Email del usuario",
     *         required=true,
     *         @OA\Schema(type="string", format="email", example="juan@example.com")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Contraseña del usuario",
     *         required=true,
     *         @OA\Schema(type="string", format="password", example="password123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Autenticación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="id_example"),
     *                     @OA\Property(property="name", type="string", example="name_example"),
     *                     @OA\Property(property="email", type="string", format="email", example="email_example"),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12T20:01:04.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12T20:01:04.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|tokenexampletokenexampletokenexample")
     *             ),
     *             @OA\Property(property="message", type="string", example="Autenticación exitosa"),
     *             @OA\Property(property="error", type="boolean", example="false")
     *         ),
     *      ),
     *      @OA\Response(
     *         response=404,
     *         description="Correo electrónico no registrado en la base de datos",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="No se encontraron resultados para la búsqueda."),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contraseña inválida",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Contraseña inválida. Asegúrate de escribirla correctamente."),
     *                 @OA\Property(property="error", type="string", example="Credenciales incorrectas."),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", example={}),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $user = User::with(['employee' => ['person']])->where('email', $request->input('email'))->firstOrFail();

            if (!Hash::check($request->input('password'), $user->password)) {
                return $this->errorResponse(
                    [
                        'message' => $this->getMessage('UnauthorizedException'),
                        'error' => 'Credenciales incorrectas.'
                    ],
                    422
                );
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->load(['roles', 'permissions']);

            return $this->successResponse(
                [
                    'user' => $user,
                    'token' => $token
                ],
                'Autenticación exitosa',
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function user()
    {
        try {
            $user = Auth::user();

            $user->load(['employee' => ['person' => ['gender', 'blood_type'], 'position' => ['area'], 'arl', 'eps', 'pension_fund', 'compensation_fund'], 'roles', 'permissions']);

            $navegation = Module::with(['submodules' => fn($query) => $query->whereIn('permission_id', Auth::user()->permissions->modelKeys())])
                ->whereHas('submodules', fn($query) => $query->whereIn('permission_id', Auth::user()->permissions->modelKeys()))
                ->get();

            return $this->successResponse(
                [
                    'user' => $user,
                    'navegation' => $navegation
                ],
                'Usuario obtenido',
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Auth"},
     *     summary="Cerrar sesión",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cierre de sesión exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="id_example"),
     *                     @OA\Property(property="name", type="string", example="name_example"),
     *                     @OA\Property(property="email", type="string", format="email", example="email_example"),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12T20:01:04.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12T20:01:04.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Cierre de sesión exitoso"),
     *             @OA\Property(property="error", type="boolean", example="false")
     *         ),
     *      ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", example={}),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        try {
            $user = Auth::user();
            $user->tokens()->delete();

            return $this->successResponse(
                [
                    'user' => $user
                ],
                'Cierre de sesión exitoso',
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
