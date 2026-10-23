<?php

namespace App\Http\Controllers;

use App\Http\Requests\Classification\Group\GroupAllRequest;
use App\Http\Requests\Classification\Group\GroupDeleteRequest;
use App\Http\Requests\Classification\Group\GroupFindRequest;
use App\Http\Requests\Classification\Group\GroupRestoreRequest;
use App\Http\Requests\Classification\Group\GroupStoreRequest;
use App\Http\Requests\Classification\Group\GroupUpdateRequest;
use App\Http\Resources\Classification\Group\GroupCollection;
use App\Http\Resources\Classification\Group\GroupResource;
use App\Models\Group;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="Group",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=96),
 *     @OA\Property(property="item_id", type="integer", example=13),
 *     @OA\Property(property="name", type="string", example="BEBÉ"),
 *     @OA\Property(property="description", type="string", example="PRENDAS PARA BEBÉ"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 15:20:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 15:20:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=13),
 *         @OA\Property(property="name", type="string", example="Grupos"),
 *         @OA\Property(property="description", type="string", example="Listado de grupos."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="GroupResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="group", ref="#/components/schemas/Group")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="GroupListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="classification/groups", type="array", @OA\Items(ref="#/components/schemas/Group")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="GroupRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los grupos.", example="BEBÉ"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="PRENDAS PARA BEBÉ")
 * )
 */
class GroupController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/classification/groups/all",
     *     tags={"Classification › Groups"},
     *     summary="Listar los grupos",
     *     description="En esta lista no viene `settings`.",
     *     security={{"sanctum":{}}},
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
     *         description="Lista de grupos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GroupListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"per_page": {"Debe ser un valor numérico."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(GroupAllRequest $request)
    {
        try {
            $groups = Group::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $groups = $groups->paginate($request->integer('per_page', $groups->count()));

            return $this->successResponse(
                new GroupCollection($groups),
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
     *     path="/classification/groups/find/{id}",
     *     tags={"Classification › Groups"},
     *     summary="Obtener un grupo",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Grupo cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GroupResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(GroupFindRequest $request, $id)
    {
        try {
            $group = Group::findOrFail($id);

            return $this->successResponse(
                new GroupResource($group),
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
     *     path="/classification/groups/store",
     *     tags={"Classification › Groups"},
     *     summary="Crear un grupo",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/GroupRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Grupo creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GroupResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(GroupStoreRequest $request)
    {
        try {
            $group = new Group();
            $group->name = $request->input('name');
            $group->description = $request->input('description');
            $group->save();

            return $this->successResponse(
                new GroupResource($group),
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
     *     path="/classification/groups/update/{id}",
     *     tags={"Classification › Groups"},
     *     summary="Editar un grupo",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/GroupRequest",
     *             example={"name": "UNISEX", "description": "PRENDAS PARA DAMA Y CABALLERO"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Grupo editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GroupResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o grupo no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del grupo", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"description": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(GroupUpdateRequest $request, $id)
    {
        try {
            $group = Group::findOrFail($id);
            $group->name = $request->input('name');
            $group->description = $request->input('description');
            $group->save();

            return $this->successResponse(
                new GroupResource($group),
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
     *     path="/classification/groups/delete/{id}",
     *     tags={"Classification › Groups"},
     *     summary="Desactivar un grupo",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Grupo desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/GroupResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(GroupDeleteRequest $request, $id)
    {
        try {
            $group = Group::findOrFail($id);
            $group->delete();

            return $this->successResponse(
                new GroupResource($group),
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
     *     path="/classification/groups/restore/{id}",
     *     tags={"Classification › Groups"},
     *     summary="Restaurar un grupo desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Grupo restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GroupResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(GroupRestoreRequest $request, $id)
    {
        try {
            $group = Group::withTrashed()->findOrFail($id);
            $group->restore();

            return $this->successResponse(
                new GroupResource($group),
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
