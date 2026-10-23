<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Module;
use App\Models\User;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Endpoints para gestionar la autenticación (inicio y cierre de sesión)"
 * )
 *
 * @OA\Schema(
 *     schema="AuthUser",
 *     description="Usuario autenticado: el de `User` más los procesos, subprocesos y operaciones que tiene asignados.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/User"),
 *         @OA\Schema(
 *             @OA\Property(property="processes", type="array", description="Procesos asignados (con `pivot`).", @OA\Items(ref="#/components/schemas/Subitem")),
 *             @OA\Property(property="subprocesses", type="array", description="Subprocesos asignados (con `pivot`).", @OA\Items(ref="#/components/schemas/Subitem")),
 *             @OA\Property(property="operations", type="array", description="Operaciones asignadas (con `pivot`).", @OA\Items(ref="#/components/schemas/Subitem"))
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="laura.gomez@kanri.test"),
 *     @OA\Property(property="password", type="string", format="password", example="Calidad2026*")
 * )
 *
 * @OA\Schema(
 *     schema="LoginResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="user", ref="#/components/schemas/AuthUser"),
 *         @OA\Property(property="token", type="string", description="Token de Sanctum en texto plano. Se muestra una sola vez y no caduca.", example="15|Xk9pQ2vR7mT4wL8nB3cY6hJ1dF5gS0aZ2eU7iO9p")
 *     ),
 *     @OA\Property(property="message", type="string", example="Autenticación exitosa"),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="AuthProfileResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="user",
 *             description="Además de lo de `AuthUser`, el empleado trae `person.gender`, `person.blood_type`, `position.area`, `risk_manager`, `health_entity`, `pension_fund` y `compensation_fund`.",
 *             allOf={@OA\Schema(ref="#/components/schemas/AuthUser")}
 *         ),
 *         @OA\Property(
 *             property="navegation",
 *             type="array",
 *             description="Menú del usuario: módulos con solo los submódulos cuyo permiso tiene asignado directamente.",
 *             @OA\Items(ref="#/components/schemas/Module")
 *         )
 *     ),
 *     @OA\Property(property="message", type="string", example="Usuario obtenido"),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 */
class AuthController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Auth"},
     *     summary="Iniciar sesión",
     *     description="Devuelve el usuario y un token nuevo, que se envía en `Authorization: Bearer <token>` (botón Authorize). **Cierra las demás sesiones**: borra todos los tokens anteriores del usuario. No requiere token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Autenticación exitosa",
     *         @OA\JsonContent(ref="#/components/schemas/LoginResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No existe un usuario con ese correo.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse",
     *             example={"error": {"message": "No se encontraron resultados para la búsqueda.", "error": "No query results for model [App\Models\User]."}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos (formato de error de validación) o contraseña incorrecta (formato `error`).",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(ref="#/components/schemas/ValidationErrorResponse"),
     *                 @OA\Schema(ref="#/components/schemas/ServerErrorResponse")
     *             },
     *             @OA\Examples(
     *                 example="validacion",
     *                 summary="Datos inválidos",
     *                 value={"message": "Error de validación.", "attributes": {"email": "Correo electrónico", "password": "Contraseña"}, "errors": {"email": {"Debe ser una dirección de correo electrónico válida."}}}
     *             ),
     *             @OA\Examples(
     *                 example="contrasena",
     *                 summary="Contraseña incorrecta",
     *                 value={"error": {"message": "Contraseña inválida. Asegúrate de escribirla correctamente.", "error": "Credenciales incorrectas."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $user = User::with(['employee' => ['person']])->where('email', $request->input('email'))->firstOrFail();

            if (! Hash::check($request->input('password'), $user->password)) {
                return $this->errorResponse(
                    [
                        'message' => $this->getMessage('UnauthorizedException'),
                        'error' => 'Credenciales incorrectas.',
                    ],
                    422
                );
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->load(['roles', 'permissions', 'processes', 'subprocesses', 'operations']);

            return $this->successResponse(
                [
                    'user' => $user,
                    'token' => $token,
                ],
                'Autenticación exitosa',
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/user",
     *     tags={"Auth"},
     *     summary="Obtener el usuario autenticado y su menú",
     *     description="Perfil completo del dueño del token y los módulos/submódulos que puede ver.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Usuario obtenido",
     *         @OA\JsonContent(ref="#/components/schemas/AuthProfileResponse")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function user()
    {
        try {
            $user = Auth::user();

            $user->load(['employee' => ['person' => ['gender', 'blood_type'], 'position' => ['area'], 'risk_manager', 'health_entity', 'pension_fund', 'compensation_fund'], 'roles', 'permissions', 'processes', 'subprocesses', 'operations']);

            $navegation = Module::with(['submodules' => fn ($query) => $query->whereIn('permission_id', Auth::user()->permissions->modelKeys())])
                ->whereHas('submodules', fn ($query) => $query->whereIn('permission_id', Auth::user()->permissions->modelKeys()))
                ->get();

            return $this->successResponse(
                [
                    'user' => $user,
                    'navegation' => $navegation,
                ],
                'Usuario obtenido',
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
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
     *     description="Borra **todos** los tokens del usuario: cierra la sesión en todos los dispositivos.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cierre de sesión exitoso. `user` llega sin relaciones.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=15),
     *                     @OA\Property(property="employee_id", type="integer", example=42),
     *                     @OA\Property(property="email", type="string", example="laura.gomez@kanri.test"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T09:50:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T09:50:00.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Cierre de sesión exitoso"),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function logout()
    {
        try {
            $user = Auth::user();
            $user->tokens()->delete();

            return $this->successResponse(
                [
                    'user' => $user,
                ],
                'Cierre de sesión exitoso',
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
