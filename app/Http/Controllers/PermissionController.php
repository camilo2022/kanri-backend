<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authorization\Permission\PermissionAllRequest;
use App\Http\Requests\Authorization\Permission\PermissionFindRequest;
use App\Http\Requests\Authorization\Permission\PermissionStoreRequest;
use App\Http\Requests\Authorization\Permission\PermissionUpdateRequest;
use App\Http\Resources\Authorization\Permission\PermissionCollection;
use App\Models\Permission;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Permission",
 *     description="Endpoints de Gestión de Permisos"
 * )
 *
 * @OA\PathItem(
 *     path="/permission",
 *     description="Rutas de Gestión de Permisos"
 * )
 */
class PermissionController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/authorization/permissions/all",
     *     tags={"Authorization"},
     *     summary="Listar los permisos",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Texto de búsqueda en los permisos",
     *         required=false,
     *         @OA\Schema(type="string", example="authorization")
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
     *         description="Lista de permisos cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="permisos",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="id_example"),
     *                         @OA\Property(property="name", type="string", example="name_permission_example"),
     *                         @OA\Property(property="title", type="string", example="title_permission_example"),
     *                         @OA\Property(property="description", type="string", example="description_permission_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                     ),
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="id_example"),
     *                         @OA\Property(property="name", type="string", example="name_permission_example"),
     *                         @OA\Property(property="title", type="string", example="title_permission_example"),
     *                         @OA\Property(property="description", type="string", example="description_permission_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                     )
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
    public function all(PermissionAllRequest $request)
    {
        try {
            $permissions = Permission::with(['roles'])
                ->when($request->filled('role_id'), fn($query) => $query->whereHas('roles', fn($q) => $q->where('id', $request->input('role_id'))))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $permissions = $permissions->paginate($request->integer('per_page', $permissions->count()));

            return $this->successResponse(
                new PermissionCollection($permissions),
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
     *     path="/authorization/permissions/find/{id}",
     *     tags={"Authorization"},
     *     summary="Obtener un permiso específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del permiso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="permission",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="id_example"),
     *                     @OA\Property(property="name", type="string", example="name_permission_example"),
     *                     @OA\Property(property="guard_name", type="string", example="guard_name_permission_example"),
     *                     @OA\Property(property="title", type="string", example="title_permission_example"),
     *                     @OA\Property(property="description", type="string", example="description_permission_example"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de permiso no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del permiso")
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
    public function find(PermissionFindRequest $request)
    {
        try {
            $permission = permission::findOrFail($request->id);

            return $this->successResponse(
                [
                    'permission' => $permission
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
     *     path="/authorization/permissions/store",
     *     tags={"Authorization"},
     *     summary="Crear un permiso",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="Identificador del rol",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nombre del permiso",
     *         required=true,
     *         @OA\Schema(type="string", example="authorization")
     *     ),
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="Título del permiso",
     *         required=true,
     *         @OA\Schema(type="string", example="authorization create")
     *     ),
     *     @OA\Parameter(
     *         name="description",
     *         in="query",
     *         description="Descripción del permiso",
     *         required=true,
     *         @OA\Schema(type="text", example="It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. ")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol creado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="permission",
     *                     type="object",
     *                     @OA\Property(property="guard_name", type="string", example="guard_name"),
     *                     @OA\Property(property="name", type="string", example="name_rol_example"),
     *                     @OA\Property(property="title", type="string", example="title_rol_example"),
     *                     @OA\Property(property="description", type="string", example="description_rol_example"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="id", type="integer", example="id_example")
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="status", type="integer", example=201)
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="role_id", type="integer", example="Identificador del rol"),
     *                 @OA\Property(property="name", type="string", example="Nombre del permiso"),
     *                 @OA\Property(property="title", type="string", example="Título del permiso"),
     *                 @OA\Property(property="description", type="string", example="Descripción del permiso")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role_id",
     *                     type="array",
     *                     @OA\Items(type="numeric", example="El identificador del rol no existe.")
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
     *                 ),
     *                 @OA\Property(
     *                     property="title",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
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

    public function store(PermissionStoreRequest $request)
    {
        try {
            $permission = new Permission();
            $permission->name = $request->input('name');
            $permission->title =  $request->input('title');
            $permission->description = $request->input('description');
            $permission->save();

            $permission->syncRoles($request->integer('role_id'));

            return $this->successResponse(
                [
                    'permission' => $permission
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
     *     path="/authorization/permissions/update/{id}",
     *     tags={"Authorization"},
     *     summary="Editar un permiso específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del permiso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="Identificador del rol",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nombre del permiso",
     *         required=true,
     *         @OA\Schema(type="string", example="authorization")
     *     ),
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="Titulo del permiso",
     *         required=true,
     *         @OA\Schema(type="string", example="authorization create")
     *     ),
     *     @OA\Parameter(
     *         name="description",
     *         in="query",
     *         description="Descripcion del permiso",
     *         required=true,
     *         @OA\Schema(type="string", example="It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. ")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso editado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="permission",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="id_example"),
     *                     @OA\Property(property="name", type="string", example="name_permission_example"),
     *                     @OA\Property(property="guard_name", type="string", example="guard_name"),
     *                     @OA\Property(property="title", type="string", example="title_permission_example"),
     *                     @OA\Property(property="description", type="string", example="description_permission_example"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="role_id", type="integer", example="role_id_example"),
     *                 @OA\Property(property="id", type="string", example="Identificador del permiso"),
     *                 @OA\Property(property="name", type="string", example="Nombre del permiso"),
     *                 @OA\Property(property="title", type="string", example="Título del permiso"),
     *                 @OA\Property(property="description", type="string", example="Descripción del permiso")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
     *                 ),
     *                 @OA\Property(
     *                     property="title",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
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
    public function update(PermissionUpdateRequest $request, $id)
    {
        try {
            $permission = Permission::findOrFail($id);
            $permission->name = $request->input('name');
            $permission->title =  $request->input('title');
            $permission->description = $request->input('description');
            $permission->save();

            $permission->syncRoles($request->integer('role_id'));

            return $this->successResponse(
                [
                    'permission' => $permission
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
