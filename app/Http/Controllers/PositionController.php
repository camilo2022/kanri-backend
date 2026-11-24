<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationalStructure\Position\PositionAllRequest;
use App\Http\Requests\OrganizationalStructure\Position\PositionAuthorizationAssignRequest;
use App\Http\Requests\OrganizationalStructure\Position\PositionAuthorizationRemoveRequest;
use App\Http\Requests\OrganizationalStructure\Position\PositionDeleteRequest;
use App\Http\Requests\OrganizationalStructure\Position\PositionFindRequest;
use App\Http\Requests\OrganizationalStructure\Position\PositionRestoreRequest;
use App\Http\Requests\OrganizationalStructure\Position\PositionStoreRequest;
use App\Http\Requests\OrganizationalStructure\Position\PositionUpdateRequest;
use App\Http\Resources\OrganizationalStructure\Position\PositionCollection;
use App\Http\Resources\OrganizationalStructure\Position\PositionResource;
use App\Models\Permission;
use App\Models\Position;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="Position",
 *     type="object",
 *     description="Cargo serializado tal cual por Eloquent, con su área. Es el que viene anidado en empleados y usuarios.",
 *     @OA\Property(property="id", type="integer", example=58),
 *     @OA\Property(property="item_id", type="integer", example=4),
 *     @OA\Property(property="name", type="string", example="INSPECTOR DE CALIDAD"),
 *     @OA\Property(property="description", type="string", example="CARGO QUE INSPECCIONA LAS PRENDAS TERMINADAS"),
 *     @OA\Property(property="settings", type="object", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T09:40:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T09:40:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="area", type="array", @OA\Items(ref="#/components/schemas/PositionArea"))
 * )
 *
 * @OA\Schema(
 *     schema="PositionWithRelations",
 *     description="Cargo serializado por Eloquent con área, roles y permisos (anidado en store/update de usuarios).",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Position"),
 *         @OA\Schema(
 *             @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/PositionRole")),
 *             @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/PositionPermission"))
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="PositionArea",
 *     description="Área del cargo, con el pivote de `model_has_subitems`.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Subitem"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="pivot",
 *                 type="object",
 *                 @OA\Property(property="model_type", type="string", example="App\Models\Position"),
 *                 @OA\Property(property="model_id", type="integer", example=58),
 *                 @OA\Property(property="subitem_id", type="integer", example=7)
 *             )
 *         )
 *     },
 *     example={"id": 7, "item_id": 3, "name": "CONTROL DE CALIDAD", "description": "ÁREA ENCARGADA DE INSPECCIONAR LAS PRENDAS TERMINADAS", "settings": {}, "created_at": "2026-05-14T09:32:10.000000Z", "updated_at": "2026-05-14T09:32:10.000000Z", "deleted_at": null, "pivot": {"model_type": "App\Models\Position", "model_id": 58, "subitem_id": 7}}
 * )
 *
 * @OA\Schema(
 *     schema="PositionRole",
 *     description="Rol asignado al cargo, con el pivote de `model_has_roles`.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Role"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="pivot",
 *                 type="object",
 *                 @OA\Property(property="model_type", type="string", example="App\Models\Position"),
 *                 @OA\Property(property="model_id", type="integer", example=58),
 *                 @OA\Property(property="role_id", type="integer", example=12)
 *             )
 *         )
 *     },
 *     example={"id": 12, "name": "technical_sheets", "guard_name": "api", "title": "Fichas técnicas", "description": "Gestión de fichas técnicas.", "created_at": "2026-03-30T13:17:29.000000Z", "updated_at": "2026-03-30T13:17:29.000000Z", "pivot": {"model_type": "App\Models\Position", "model_id": 58, "role_id": 12}}
 * )
 *
 * @OA\Schema(
 *     schema="PositionPermission",
 *     description="Permiso asignado al cargo, con el pivote de `model_has_permissions`.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Permission"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="pivot",
 *                 type="object",
 *                 @OA\Property(property="model_type", type="string", example="App\Models\Position"),
 *                 @OA\Property(property="model_id", type="integer", example=58),
 *                 @OA\Property(property="permission_id", type="integer", example=131)
 *             )
 *         )
 *     },
 *     example={"id": 131, "name": "technical_sheets.find", "guard_name": "api", "title": "Consultar ficha técnica", "description": "Permite consultar una ficha técnica.", "created_at": "2026-03-30T13:17:29.000000Z", "updated_at": "2026-03-30T13:17:29.000000Z", "pivot": {"model_type": "App\Models\Position", "model_id": 58, "permission_id": 131}}
 * )
 *
 * @OA\Schema(
 *     schema="PositionDetail",
 *     type="object",
 *     description="Cargo como lo entrega `PositionResource` (find, store, update, delete, restore y autorización).",
 *     @OA\Property(property="id", type="integer", example=58),
 *     @OA\Property(property="item_id", type="integer", example=4),
 *     @OA\Property(property="name", type="string", example="INSPECTOR DE CALIDAD"),
 *     @OA\Property(property="description", type="string", example="CARGO QUE INSPECCIONA LAS PRENDAS TERMINADAS"),
 *     @OA\Property(property="settings", type="object", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 09:40:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 09:40:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         description="Solo viene cargado en find, store, update, delete y restore.",
 *         @OA\Property(property="id", type="integer", example=4),
 *         @OA\Property(property="name", type="string", example="Cargos"),
 *         @OA\Property(property="description", type="string", example="Listado de cargos."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(property="area", type="array", description="Lista con el área del cargo.", @OA\Items(ref="#/components/schemas/PositionArea")),
 *     @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/PositionRole")),
 *     @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/PositionPermission"))
 * )
 *
 * @OA\Schema(
 *     schema="PositionListItem",
 *     type="object",
 *     description="Cargo como lo entrega `PositionCollection` (all): sin `item_id`, `settings` ni `item`, y `area` es un objeto, no una lista.",
 *     @OA\Property(property="id", type="integer", example=58),
 *     @OA\Property(property="name", type="string", example="INSPECTOR DE CALIDAD"),
 *     @OA\Property(property="description", type="string", example="CARGO QUE INSPECCIONA LAS PRENDAS TERMINADAS"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 09:40:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 09:40:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="area", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/PositionArea")}),
 *     @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/PositionRole")),
 *     @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/PositionPermission"))
 * )
 *
 * @OA\Schema(
 *     schema="PositionResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="position", ref="#/components/schemas/PositionDetail")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PositionListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="positions", type="array", @OA\Items(ref="#/components/schemas/PositionListItem")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PositionRequest",
 *     type="object",
 *     required={"area_id", "name", "description"},
 *     @OA\Property(property="area_id", type="integer", description="Área a la que pertenece el cargo.", example=7),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         minLength=4,
 *         maxLength=50,
 *         description="Nombre en MAYÚSCULAS. Único entre los cargos.",
 *         example="INSPECTOR DE CALIDAD"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         maxLength=255,
 *         description="Descripción en MAYÚSCULAS.",
 *         example="CARGO QUE INSPECCIONA LAS PRENDAS TERMINADAS"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PositionPermissionRequest",
 *     type="object",
 *     required={"permission_id"},
 *     @OA\Property(property="permission_id", type="integer", description="Identificador del permiso.", example=131)
 * )
 */
class PositionController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/organizational_structure/areas/positions/all/{area_id}",
     *     tags={"Organizational Structure › Positions"},
     *     summary="Listar los cargos de un área",
     *     description="Cada cargo trae su área, roles y permisos. Un `area_id` inexistente devuelve una lista vacía.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="area_id",
     *         in="path",
     *         required=true,
     *         description="Identificador del área.",
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "name", "description", "created_at", "updated_at", "deleted_at"}, example="name")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(ref="#/components/parameters/WithTrashedOptional"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de cargos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PositionListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"column": {"Valor inválido. Permitidos: id, name, description, created_at, updated_at, deleted_at."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(PositionAllRequest $request, $area_id)
    {
        try {
            $positions = Position::with(['area', 'roles', 'permissions'])
                ->whereHas('area', fn($r) => $r->where('id', $area_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $positions = $positions->paginate($request->integer('per_page', $positions->count()));

            return $this->successResponse(
                new PositionCollection($positions),
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
     *     path="/organizational_structure/areas/positions/find/{id}",
     *     tags={"Organizational Structure › Positions"},
     *     summary="Obtener un cargo",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Cargo cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PositionResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(PositionFindRequest $request, $id)
    {
        try {
            $position = Position::with(['area', 'roles', 'permissions'])
                ->findOrFail($id);

            return $this->successResponse(
                new PositionResource($position),
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
     *     path="/organizational_structure/areas/positions/store",
     *     tags={"Organizational Structure › Positions"},
     *     summary="Crear un cargo",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PositionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cargo creado correctamente. `roles` y `permissions` llegan vacíos.",
     *         @OA\JsonContent(ref="#/components/schemas/PositionResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"area_id": "Identificador del área", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"area_id": {"No está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(PositionStoreRequest $request)
    {
        try {
            $position = new Position();
            $position->name = $request->input('name');
            $position->description = $request->input('description');
            $position->save();

            $position->area()->sync([$request->integer('area_id')]);

            return $this->successResponse(
                new PositionResource($position),
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
     *     path="/organizational_structure/areas/positions/update/{id}",
     *     tags={"Organizational Structure › Positions"},
     *     summary="Editar un cargo",
     *     description="Reemplaza el área del cargo por `area_id`. Hoy `area_id` no se valida en este endpoint (ver pendientes): envíalo siempre.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/PositionRequest",
     *             example={"area_id": 7, "name": "SUPERVISOR DE CALIDAD", "description": "CARGO QUE COORDINA A LOS INSPECTORES DE CALIDAD"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cargo editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PositionResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o cargo no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del cargo", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(PositionUpdateRequest $request, $id)
    {
        try {
            $position = Position::findOrFail($id);
            $position->name = $request->input('name');
            $position->description = $request->input('description');
            $position->save();

            $position->area()->sync([$request->integer('area_id')]);

            return $this->successResponse(
                new PositionResource($position),
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
     *     path="/organizational_structure/areas/positions/delete/{id}",
     *     tags={"Organizational Structure › Positions"},
     *     summary="Desactivar un cargo",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Cargo desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/PositionResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(PositionDeleteRequest $request, $id)
    {
        try {
            $position = Position::findOrFail($id);
            $position->delete();

            return $this->successResponse(
                new PositionResource($position),
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
     *     path="/organizational_structure/areas/positions/restore/{id}",
     *     tags={"Organizational Structure › Positions"},
     *     summary="Restaurar un cargo desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Cargo restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PositionResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(PositionRestoreRequest $request, $id)
    {
        try {
            $position = Position::withTrashed()->findOrFail($id);
            $position->restore();

            return $this->successResponse(
                new PositionResource($position),
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
     *     path="/organizational_structure/areas/positions/authorization/assign/{id}",
     *     tags={"Organizational Structure › Positions"},
     *     summary="Asignar un permiso a un cargo",
     *     description="Además del permiso, asigna al cargo los roles a los que pertenece ese permiso si aún no los tiene.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PositionPermissionRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso asignado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PositionResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cargo o permiso inexistente, o el cargo ya tiene el permiso.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del cargo", "permission_id": "Identificador del permiso"},
     *                 "errors": {"permission_id": {"No hay ningún registro."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function assign(PositionAuthorizationAssignRequest $request, $id)
    {
        try {
            $permission = Permission::findOrFail($request->input('permission_id'));
            $position = Position::findOrFail($id);

            $position->givePermissionTo($permission);

            if (!$position->hasRole($permission->roles)) {
                $position->assignRole($permission->roles);
            }

            $position->load(['area', 'roles', 'permissions']);

            return $this->successResponse(
                new PositionResource($position),
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
     *     path="/organizational_structure/areas/positions/authorization/remove/{id}",
     *     tags={"Organizational Structure › Positions"},
     *     summary="Remover un permiso de un cargo",
     *     description="Además del permiso, quita al cargo los roles con los que ya no comparte ningún permiso.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PositionPermissionRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso removido correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PositionResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cargo o permiso inexistente, o el cargo no tiene ese permiso.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del cargo", "permission_id": "Identificador del permiso"},
     *                 "errors": {"permission_id": {"No hay ningún registro."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function remove(PositionAuthorizationRemoveRequest $request, $id)
    {
        try {
            $permission = Permission::findOrFail($request->input('permission_id'));
            $position = Position::findOrFail($id);

            $position->revokePermissionTo($permission);

            $position->roles->each(function ($role) use ($position) {
                if ($position->permissions->pluck('name')->intersect($role->permissions->pluck('name'))->isEmpty()) {
                    $position->removeRole($role);
                }
            });

            $position->load(['area', 'roles', 'permissions']);

            return $this->successResponse(
                new PositionResource($position),
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
