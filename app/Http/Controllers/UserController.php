<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UserAllRequest;
use App\Http\Requests\User\UserAssignRequest;
use App\Http\Requests\User\UserDeleteRequest;
use App\Http\Requests\User\UserFindRequest;
use App\Http\Requests\User\UserRemoveRequest;
use App\Http\Requests\User\UserRestoreRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\User\UserCollection;
use App\Models\Permission;
use App\Models\User;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasPermissions;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Endpoints para gestionar usuarios"
 * )
 *
 * @OA\PathItem(
 *     path="/users",
 *     description="Rutas de gestión de usuarios"
 * )
 */
class UserController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/users/all",
     *     tags={"Users"},
     *     summary="Listar los usuarios",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Texto de búsqueda en los usuarios",
     *         required=false,
     *         @OA\Schema(type="string", example="juan")
     *     ),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         description="Columna por la que se ordena",
     *         required=false,
     *         @OA\Schema(type="string", example="name")
     *     ),
     *     @OA\Parameter(
     *         name="dir",
     *         in="query",
     *         description="Dirección de ordenamiento",
     *         required=false,
     *         @OA\Schema(type="string", example="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Cantidad de registros por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuarios cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "users",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="id_example"),
     *                         @OA\Property(property="name", type="string", example="name_user_example"),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                     ),
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(
     *                         property="pagination",
     *                         type="object",
     *                         @OA\Property(property="total", type="integer", example=1),
     *                         @OA\Property(property="count", type="integer", example=1),
     *                         @OA\Property(property="per_page", type="integer", example=1),
     *                         @OA\Property(property="current_page", type="integer", example=1),
     *                         @OA\Property(property="total_pages", type="integer", example=1)
     *                     )
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autenticado."),
     *             @OA\Property(property="error", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function all(UserAllRequest $request)
    {
        try {
            $users = User::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->withTrashed()
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $users = $users->paginate($request->integer('per_page'), ['*'], 'page', $request->integer('page', 1));

            return $this->successResponse(
                new UserCollection($users),
                $this->getMessage('Success'),
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
     * @OA\Get(
     *     path="/users/find/{id}",
     *     tags={"Users"},
     *     summary="Obtener un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=0),
     *                         @OA\Property(property="name", type="string", example="name_user_example"),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(
     *                             property= "roles",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=0),
     *                                 @OA\Property(property="name", type="string", example="name_rol_example"),
     *                                 @OA\Property(property="guard_name", type="string", example="guard_name_rol_example"),
     *                                 @OA\Property(property="title", type="string", example="title_rol_example"),
     *                                 @OA\Property(property="description", type="string", example="description_rol_example"),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                                 @OA\Property(
     *                                     property= "pivot",
     *                                     type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="model_type", type="strig", example="Example\\Example\\Example"),
     *                                         @OA\Property(property="model_id", type="integer", example=0),
     *                                         @OA\Property(property="role_id", type="integer", example=0)
     *                                     )
     *                                 )
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property= "permission",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=0),
     *                                 @OA\Property(property="name", type="string", example="name_permission_example"),
     *                                 @OA\Property(property="guard_name", type="string", example="guard_name_permission_example"),
     *                                 @OA\Property(property="title", type="string", example="title_permission_example"),
     *                                 @OA\Property(property="description", type="string", example="description_permission_example"),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                                 @OA\Property(
     *                                     property= "pivot",
     *                                     type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="model_type", type="strig", example="Example\\Example\\Example"),
     *                                         @OA\Property(property="model_id", type="integer", example=0),
     *                                         @OA\Property(property="permission_id", type="integer", example=0)
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de usuario no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autenticado."),
     *             @OA\Property(property="error", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function find(UserFindRequest $request)
    {
        try {
            $user = User::with(['roles', 'permissions'])->findOrFail($request->id);
            return $this->successResponse(
                [
                    'user' => $user
                ],
                $this->getMessage('Success'),
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
     *     path="/users/store",
     *     tags={"Users"},
     *     summary="Crear un usuario",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nombre del usuario",
     *         required=true,
     *         @OA\Schema(type="string", example="user name")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Correo electrónico del usuario",
     *         required=true,
     *         @OA\Schema(type="string", format="email", example="user@example.com")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Contraseña del usuario",
     *         required=true,
     *         @OA\Schema(type="string", example="Contrasena12!")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="id_example"),
     *                         @OA\Property(property="name", type="string", example="name_user_example"),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="delete_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario"),
     *                 @OA\Property(property="name", type="string", example="Nombre del usuario"),
     *                 @OA\Property(property="email", type="string", example="Correo electrónico del usuario"),
     *                 @OA\Property(property="password", type="string", example="Contraseña del usuario")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya está registrado.")
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string", example="Debe ser un correo electrónico válido.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autenticado."),
     *             @OA\Property(property="error", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function store(UserStoreRequest $request)
    {
        try {
            $user = new User();

            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));

            $user->save();

            return $this->successResponse(
                [
                    'user' => $user
                ],
                $this->getMessage('Success'),
                201
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
     * @OA\Put(
     *     path="/users/update/{id}",
     *     tags={"Users"},
     *     summary="Editar un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nombre del usuario",
     *         required=true,
     *         @OA\Schema(type="string", example="user name")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Correo electrónico del usuario",
     *         required=true,
     *         @OA\Schema(type="string", format="email", example="user@example.com")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Contraseña del usuario",
     *         required=true,
     *         @OA\Schema(type="string", example="Contraseña12!!")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="name_user_example"),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="id", type="integer", example="id_example")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del usuario"),
     *                 @OA\Property(property="email", type="string", example="Correo electrónico del usuario"),
     *                 @OA\Property(property="password", type="string", example="Contraseña del usuario")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya está registrado.")
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya está registrado.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autenticado."),
     *             @OA\Property(property="error", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function update(UserUpdateRequest $request, $id)
    {
        try {
            $user = User::findorFail($id);

            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));

            $user->save();

            return $this->successResponse(
                [
                    'user' => $user
                ],
                $this->getMessage('Success'),
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
     * @OA\Delete(
     *     path="/users/delete/{id}",
     *     tags={"Users"},
     *     summary="Desactivar un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="id_example"),
     *                         @OA\Property(property="name", type="string", example="name_user_example"),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                     ),
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de usuario no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autenticado."),
     *             @OA\Property(property="error", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function delete(UserDeleteRequest $request)
    {
        try {
            $user = User::findOrFail($request->id);
            $user->delete();

            return $this->successResponse(
                [
                    'user' => $user
                ],
                $this->getMessage('Success'),
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
     * @OA\Patch(
     *     path="/users/restore/{id}",
     *     tags={"Users"},
     *     summary="Activar un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario restaurado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="id_example"),
     *                         @OA\Property(property="name", type="string", example="name_user_example"),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", example="null")
     *                     ),
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de usuario no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autenticado."),
     *             @OA\Property(property="error", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function restore(UserRestoreRequest $request)
    {
        try {
            $user = User::withTrashed()->findOrFail($request->id);
            $user->restore();

            return $this->successResponse(
                [
                    'user' => $user
                ],
                $this->getMessage('Success'),
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
     *     path="/users/authorization/assign",
     *     tags={"Users"},
     *     summary="Asignar un permiso a un usuario específio",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="permission_id",
     *         in="query",
     *         description="Identificador del Permiso",
     *         required=true,
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso asignado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="id_example"),
     *                         @OA\Property(property="name", type="string", example="name_user_example"),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="delete_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario"),
     *                 @OA\Property(property="permission_id", type="string", example="Identificador del permiso"),
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example= "No hay ningún registro.")
     *                 ),
     *                 @OA\Property(
     *                     property="permission_id",
     *                     type="array",
     *                     @OA\Items(type="string", example= "No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autenticado."),
     *             @OA\Property(property="error", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function assign(UserAssignRequest $request)
    {
        try {
            $permission = Permission::findOrFail($request->input('permission_id'));
            $user = User::findOrFail($request->input('id'));

            $user->givePermissionTo($permission);

            $user->assignRole($permission->roles);

            return $this->successResponse(
                [
                    'user' => $user
                ],
                $this->getMessage('Success'),
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
     *     path="/users/authorization/remove",
     *     tags={"Users"},
     *     summary="Remover un permiso a un usuario específio",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="permission_id",
     *         in="query",
     *         description="Identificador del Permiso",
     *         required=true,
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso removido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="id_example"),
     *                         @OA\Property(property="name", type="string", example="name_user_example"),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="delete_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario"),
     *                 @OA\Property(property="permission_id", type="string", example="Identificador del permiso"),
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example= "No hay ningún registro.")
     *                 ),
     *                 @OA\Property(
     *                     property="permission_id",
     *                     type="array",
     *                     @OA\Items(type="string", example= "No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autenticado."),
     *             @OA\Property(property="error", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function remove(UserRemoveRequest $request)
    {
        try {
            $permission = Permission::findOrFail($request->input('permission_id'));
            $user = User::findOrFail($request->input('id'));

            $user->revokePermissionTo($permission);

            foreach ($user->roles as $role) {

                $rolePermissions = $role->permissions;
                $hasPermissionFromRole = false;

                foreach ($rolePermissions as $rolePermission) {

                    if ($user->hasDirectPermission($rolePermission)) {
                        $hasPermissionFromRole = true;
                        break;
                    }
                }

                if (!$hasPermissionFromRole) {
                    $user->removeRole($role);
                }
            }

            return $this->successResponse(
                [
                    'user' => $user
                ],
                $this->getMessage('Success'),
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
