<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authorization\Role\RoleAllRequest;
use App\Http\Requests\Authorization\Role\RoleFindRequest;
use App\Http\Requests\Authorization\Role\RoleStoreRequest;
use App\Http\Requests\Authorization\Role\RoleUpdateRequest;
use App\Http\Resources\Authorization\Role\RoleCollection;
use App\Http\Resources\Authorization\Role\RoleResource;
use App\Models\Role;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RolePermission",
 *     description="Permiso del rol, con el pivote de `role_has_permissions`.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Permission"),
 *         @OA\Schema(@OA\Property(property="pivot", type="object", @OA\Property(property="role_id", type="integer", example=52), @OA\Property(property="permission_id", type="integer", example=371)))
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="RoleListItem",
 *     type="object",
 *     description="Rol como lo entrega `RoleCollection` (all), con sus permisos.",
 *     @OA\Property(property="id", type="integer", example=52),
 *     @OA\Property(property="name", type="string", example="dispatches"),
 *     @OA\Property(property="title", type="string", example="Despachos."),
 *     @OA\Property(property="description", type="string", example="Gestión de despachos."),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 17:25:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 17:25:00"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/RolePermission"))
 * )
 *
 * @OA\Schema(
 *     schema="RoleDetail",
 *     type="object",
 *     description="Rol como lo entrega `RoleResource` (find, store, update). Trae `item_id`, `settings`, `deleted_at` e `item` siempre en `null`, porque el Resource está copiado de los catálogos (ver pendientes). En `store` y `update`, `permissions` viene vacío o con los que ya tenía.",
 *     @OA\Property(property="id", type="integer", example=52),
 *     @OA\Property(property="item_id", nullable=true, example=null),
 *     @OA\Property(property="name", type="string", example="dispatches"),
 *     @OA\Property(property="description", type="string", example="Gestión de despachos."),
 *     @OA\Property(property="title", type="string", example="Despachos."),
 *     @OA\Property(property="settings", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 17:25:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 17:25:00"),
 *     @OA\Property(property="deleted_at", nullable=true, example=null),
 *     @OA\Property(property="item", nullable=true, example=null),
 *     @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/RolePermission"))
 * )
 *
 * @OA\Schema(
 *     schema="RoleResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="role", ref="#/components/schemas/RoleDetail")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="RoleListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/RoleListItem")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="RoleRequest",
 *     type="object",
 *     required={"name", "title", "description"},
 *     @OA\Property(property="name", type="string", maxLength=80, pattern="^[a-z_]+$", description="Solo minúsculas y guiones bajos. Único. Es el prefijo de los nombres de sus permisos (`dispatches.all`, …).", example="dispatches"),
 *     @OA\Property(property="title", type="string", maxLength=100, description="Único.", example="Despachos."),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Única.", example="Gestión de despachos.")
 * )
 */
class RoleController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/authorization/roles/all",
     *     tags={"Authorization › Roles"},
     *     summary="Listar los roles",
     *     description="Cada rol trae sus permisos. La búsqueda mira nombre, título y descripción. Sin borrado lógico: no hay `with_trashed`. También la pueden usar quienes gestionan submódulos, permisos, la autorización de usuarios o de cargos (ver el `can:` de la ruta).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Cada palabra se busca en nombre, título y descripción.",
     *         @OA\Schema(type="string", example="despachos")
     *     ),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "name", "title", "description", "created_at", "updated_at"}, example="title")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de roles cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RoleListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"per_page": "Registros por pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden."}, "errors": {"column": {"El valor de la columna no es válido."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
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
                    return $query->sort($request->input('column'), $request->input('dir'));
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
     *     tags={"Authorization › Roles"},
     *     summary="Obtener un rol",
     *     description="Trae el rol con sus permisos.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Rol cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RoleResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(RoleFindRequest $request, $id)
    {
        try {
            $role = Role::with(['permissions'])
                ->findOrFail($id);

            return $this->successResponse(
                new RoleResource($role),
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
     *     tags={"Authorization › Roles"},
     *     summary="Crear un rol",
     *     description="Crea el rol sin permisos. Los permisos se crean después en `/authorization/permissions/store` indicando este `role_id`.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RoleRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rol creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RoleResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre del rol", "title": "Título del rol", "description": "Descripción del rol"},
     *                 "errors": {"name": {"Formato inválido. Solo permite letras minúsculas y sin espacios."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
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
                new RoleResource($role),
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
     *     tags={"Authorization › Roles"},
     *     summary="Editar un rol",
     *     description="Cambia nombre, título y descripción; no toca sus permisos. Cambiar el nombre no renombra los permisos que empiezan con el nombre anterior.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/RoleRequest",
     *             example={"name": "dispatches", "title": "Despachos y entregas.", "description": "Gestión de despachos y entregas a clientes."}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RoleResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o rol no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del rol", "name": "Nombre del rol", "title": "Título del rol", "description": "Descripción del rol"},
     *                 "errors": {"title": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
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
                new RoleResource($role),
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
