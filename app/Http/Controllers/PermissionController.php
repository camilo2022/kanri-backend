<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authorization\Permission\PermissionAllRequest;
use App\Http\Requests\Authorization\Permission\PermissionFindRequest;
use App\Http\Requests\Authorization\Permission\PermissionStoreRequest;
use App\Http\Requests\Authorization\Permission\PermissionUpdateRequest;
use App\Http\Resources\Authorization\Permission\PermissionCollection;
use App\Http\Resources\Authorization\Permission\PermissionResource;
use App\Models\Permission;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="PermissionRole",
 *     description="Rol al que pertenece el permiso, con el pivote de `role_has_permissions`.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Role"),
 *         @OA\Schema(@OA\Property(property="pivot", type="object", @OA\Property(property="permission_id", type="integer", example=371), @OA\Property(property="role_id", type="integer", example=52)))
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="PermissionListItem",
 *     type="object",
 *     description="Permiso como lo entrega `PermissionCollection` (all): con su rol en `role` (solo el primero).",
 *     @OA\Property(property="id", type="integer", example=371),
 *     @OA\Property(property="name", type="string", example="dispatches.all"),
 *     @OA\Property(property="title", type="string", example="Listar despachos."),
 *     @OA\Property(property="description", type="string", example="Permite listar despachos."),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 17:30:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 17:30:00"),
 *     @OA\Property(property="role", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/PermissionRole")})
 * )
 *
 * @OA\Schema(
 *     schema="PermissionDetail",
 *     type="object",
 *     description="Permiso como lo entrega `PermissionResource` (find, store, update): con `roles` (lista). Trae `item_id`, `settings`, `deleted_at` e `item` siempre en `null`, porque el Resource está copiado de los catálogos (ver pendientes).",
 *     @OA\Property(property="id", type="integer", example=371),
 *     @OA\Property(property="item_id", nullable=true, example=null),
 *     @OA\Property(property="name", type="string", example="dispatches.all"),
 *     @OA\Property(property="title", type="string", example="Listar despachos."),
 *     @OA\Property(property="description", type="string", example="Permite listar despachos."),
 *     @OA\Property(property="settings", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 17:30:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 17:30:00"),
 *     @OA\Property(property="deleted_at", nullable=true, example=null),
 *     @OA\Property(property="item", nullable=true, example=null),
 *     @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/PermissionRole"))
 * )
 *
 * @OA\Schema(
 *     schema="PermissionResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="permission", ref="#/components/schemas/PermissionDetail")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PermissionListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/PermissionListItem")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PermissionRequest",
 *     type="object",
 *     required={"role_id", "name", "title", "description"},
 *     @OA\Property(property="role_id", type="integer", description="Rol al que pertenece el permiso. Al guardar, el permiso queda solo en este rol (reemplaza los anteriores).", example=52),
 *     @OA\Property(property="name", type="string", maxLength=80, pattern="^[a-z._]+$", description="Solo minúsculas, puntos y guiones bajos: `<prefijo.de.la.ruta>.<acción>`. Único. Debe coincidir con el `can:` de la ruta que protege.", example="dispatches.all"),
 *     @OA\Property(property="title", type="string", maxLength=100, description="Único.", example="Listar despachos."),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Única.", example="Permite listar despachos.")
 * )
 */
class PermissionController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/authorization/permissions/all",
     *     tags={"Authorization › Permissions"},
     *     summary="Listar los permisos",
     *     description="La búsqueda mira nombre, título y descripción del permiso y de su rol. Sin borrado lógico: no hay `with_trashed`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         required=false,
     *         description="Solo los permisos de este rol.",
     *         @OA\Schema(type="integer", example=52)
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "name", "title", "description", "created_at", "updated_at"}, example="name")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de permisos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PermissionListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"role_id": "", "column": "Columna a ordenar."}, "errors": {"column": {"El valor de la columna no es válido. "}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
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
     *     tags={"Authorization › Permissions"},
     *     summary="Obtener un permiso",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PermissionResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(PermissionFindRequest $request, $id)
    {
        try {
            $permission = Permission::with(['roles'])
                ->findOrFail($id);

            return $this->successResponse(
                new PermissionResource($permission),
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
     *     tags={"Authorization › Permissions"},
     *     summary="Crear un permiso",
     *     description="Crea el permiso y lo asigna al rol indicado.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PermissionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permiso creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PermissionResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"role_id": "Identificador del rol.", "name": "Nombre del permiso", "title": "Título del permiso", "description": "Descripción del permiso"},
     *                 "errors": {"name": {"Formato inválido. Solo permite letras minúsculas y punto."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
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
                new PermissionResource($permission),
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
     *     tags={"Authorization › Permissions"},
     *     summary="Editar un permiso",
     *     description="Cambia nombre, título y descripción, y deja el permiso solo en el rol indicado. Cambiar el nombre afecta a las rutas que lo exigen en `can:`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/PermissionRequest",
     *             example={"role_id": 52, "name": "dispatches.export", "title": "Exportar despachos.", "description": "Permite exportar despachos."}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PermissionResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o permiso no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"role_id": "Identificador del rol", "id": "Identificador del permiso", "name": "Nombre del permiso", "title": "Título del permiso", "description": "Descripción del permiso"},
     *                 "errors": {"title": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
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
                new PermissionResource($permission),
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
