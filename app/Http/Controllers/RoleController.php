<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authorization\Role\RoleAllRequest;
use App\Http\Requests\Authorization\Role\RoleFindRequest;
use App\Http\Requests\Authorization\Role\RoleStoreRequest;
use App\Http\Requests\Authorization\Role\RoleUpdateRequest;
use App\Http\Resources\Authorization\Role\RoleCollection;
use App\Models\Role;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Role",
 *     description="Endpoints de Gestión de Roles"
 * )
 *
 * @OA\PathItem(
 *     path="/rol",
 *     description="Rutas de Gestion de Roles"
 * )
 */
class RoleController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/authorization/roles/all",
     *     tags={"Authorization"},
     *     summary="Listar los roles",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Texto de búsqueda en los roles",
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
     *         description="Lista de roles cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="roles",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="id_example"),
     *                         @OA\Property(property="name", type="string", example="name_rol_example"),
     *                         @OA\Property(property="title", type="string", example="title_rol_example"),
     *                         @OA\Property(property="description", type="string", example="description_rol_example"),
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
    public function all(RoleAllRequest $request)
    {
        try {
            $roles = Role::with(['permissions'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $roles = $roles->paginate($request->integer('per_page', $roles->count()));

            return $this->successResponse(
                new RoleCollection($roles),
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
     *     path="/authorization/roles/find/{id}",
     *     tags={"Authorization"},
     *     summary="Obtener un rol específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del rol",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="id_example"),
     *                     @OA\Property(property="name", type="string", example="name_rol_example"),
     *                     @OA\Property(property="guard_name", type="string", example="guard_name_rol_example"),
     *                     @OA\Property(property="title", type="string", example="title_rol_example"),
     *                     @OA\Property(property="description", type="string", example="description_rol_example"),
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
     *         description="Identificador de rol no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del rol")
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
    public function find(RoleFindRequest $request)
    {
        try {
            $role = Role::findOrFail($request->id);

            return $this->successResponse(
                [
                    'role' => $role
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
     *     path="/authorization/roles/store",
     *     tags={"Authorization"},
     *     summary="Crear un rol",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nombre del rol",
     *         required=true,
     *         @OA\Schema(type="string", example="authorization")
     *     ),
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="Titulo del rol",
     *         required=true,
     *         @OA\Schema(type="string", example="authorization create")
     *     ),
     *     @OA\Parameter(
     *         name="description",
     *         in="query",
     *         description="Descripcion del rol",
     *         required=true,
     *         @OA\Schema(type="string", example="It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. ")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol creado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role",
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
     *                 @OA\Property(property="name", type="string", example="Nombre del rol"),
     *                 @OA\Property(property="title", type="string", example="Título del rol"),
     *                 @OA\Property(property="description", type="string", example="Descripción del rol")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
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
    public function store(RoleStoreRequest $request)
    {
        try {
            $role = new Role();
            $role->name = $request->input('name');
            $role->title =  $request->input('title');
            $role->description = $request->input('description');
            $role->save();

            return $this->successResponse(
                [
                    'role' => $role
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
     *     path="/authorization/roles/update/{id}",
     *     tags={"Authorization"},
     *     summary="Editar un rol específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del rol",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nombre del rol",
     *         required=true,
     *         @OA\Schema(type="string", example="authorization")
     *     ),
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="Titulo del rol",
     *         required=true,
     *         @OA\Schema(type="string", example="authorization create")
     *     ),
     *     @OA\Parameter(
     *         name="description",
     *         in="query",
     *         description="Descripcion del rol",
     *         required=true,
     *         @OA\Schema(type="string", example="t is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. ")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol editado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="id_example"),
     *                     @OA\Property(property="name", type="string", example="name_rol_example"),
     *                     @OA\Property(property="guard_name", type="string", example="guard_name"),
     *                     @OA\Property(property="title", type="string", example="title_rol_example"),
     *                     @OA\Property(property="description", type="string", example="description_rol_example"),
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
     *                 @OA\Property(property="id", type="string", example="Identificador del rol"),
     *                 @OA\Property(property="name", type="string", example="Nombre del rol"),
     *                 @OA\Property(property="title", type="string", example="Título del rol"),
     *                 @OA\Property(property="description", type="string", example="Descripción del rol")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
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
    public function update(RoleUpdateRequest $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->name = $request->input('name');
            $role->title =  $request->input('title');
            $role->description = $request->input('description');
            $role->save();

            return $this->successResponse(
                [
                    'role' => $role
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
