<?php

namespace App\Http\Controllers;

use App\Http\Requests\HealthEntity\HealthEntityAllRequest;
use App\Http\Requests\HealthEntity\HealthEntityDeleteRequest;
use App\Http\Requests\HealthEntity\HealthEntityFindRequest;
use App\Http\Requests\HealthEntity\HealthEntityRestoreRequest;
use App\Http\Requests\HealthEntity\HealthEntityStoreRequest;
use App\Http\Requests\HealthEntity\HealthEntityUpdateRequest;
use App\Http\Resources\HealthEntity\HealthEntityCollection;
use App\Http\Resources\HealthEntity\HealthEntityResource;
use App\Models\HealthEntity;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Health Entities",
 *     description="Endpoints para gestionar entidades de salud"
 * )
 *
 * @OA\Schema(
 *     schema="HealthEntity",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=101),
 *     @OA\Property(property="item_id", type="integer", example=8),
 *     @OA\Property(property="name", type="string", example="ALIANSALUD"),
 *     @OA\Property(property="description", type="string", example="ALIANSALUD ENTIDAD PROMOTORA DE SALUD"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 15:40:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 15:40:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=8),
 *         @OA\Property(property="name", type="string", example="Entidades de Salud"),
 *         @OA\Property(property="description", type="string", example="Listado de entidades promotoras de salud."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="HealthEntityResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="health_entity", ref="#/components/schemas/HealthEntity")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="HealthEntityListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="health_entities", type="array", @OA\Items(ref="#/components/schemas/HealthEntity")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="HealthEntityRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=100, description="Nombre en MAYÚSCULAS. Único entre las entidades de salud.", example="ALIANSALUD"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="ALIANSALUD ENTIDAD PROMOTORA DE SALUD")
 * )
 */
class HealthEntityController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/health_entities/all",
     *     tags={"Health Entities"},
     *     summary="Listar las entidades de salud",
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
     *         description="Lista de entidades de salud cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/HealthEntityListResponse")
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
    public function all(HealthEntityAllRequest $request)
    {
        try {
            $health_entities = HealthEntity::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $health_entities = $health_entities->paginate($request->integer('per_page', $health_entities->count()));

            return $this->successResponse(
                new HealthEntityCollection($health_entities),
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
     *     path="/health_entities/find/{id}",
     *     tags={"Health Entities"},
     *     summary="Obtener una entidad de salud",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Entidad de salud cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/HealthEntityResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(HealthEntityFindRequest $request, $id)
    {
        try {
            $health_entity = HealthEntity::findOrFail($id);

            return $this->successResponse(
                new HealthEntityResource($health_entity),
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
     *     path="/health_entities/store",
     *     tags={"Health Entities"},
     *     summary="Crear una entidad de salud",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/HealthEntityRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Entidad de salud creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/HealthEntityResponse")
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
    public function store(HealthEntityStoreRequest $request)
    {
        try {
            $health_entity = new HealthEntity();
            $health_entity->name = $request->input('name');
            $health_entity->description = $request->input('description');
            $health_entity->save();

            return $this->successResponse(
                new HealthEntityResource($health_entity),
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
     *     path="/health_entities/update/{id}",
     *     tags={"Health Entities"},
     *     summary="Editar una entidad de salud",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/HealthEntityRequest",
     *             example={"name": "CAPRESOCA", "description": "CAPRESOCA EPS"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Entidad de salud editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/HealthEntityResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o entidad de salud no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador de la entidad de salud", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"description": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(HealthEntityUpdateRequest $request, $id)
    {
        try {
            $health_entity = HealthEntity::findOrFail($id);
            $health_entity->name = $request->input('name');
            $health_entity->description = $request->input('description');
            $health_entity->save();

            return $this->successResponse(
                new HealthEntityResource($health_entity),
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
     *     path="/health_entities/delete/{id}",
     *     tags={"Health Entities"},
     *     summary="Desactivar una entidad de salud",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Entidad de salud desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/HealthEntityResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(HealthEntityDeleteRequest $request, $id)
    {
        try {
            $health_entity = HealthEntity::findOrFail($id);
            $health_entity->delete();

            return $this->successResponse(
                new HealthEntityResource($health_entity),
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
     *     path="/health_entities/restore/{id}",
     *     tags={"Health Entities"},
     *     summary="Restaurar una entidad de salud desactivada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Entidad de salud restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/HealthEntityResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(HealthEntityRestoreRequest $request, $id)
    {
        try {
            $health_entity = HealthEntity::withTrashed()->findOrFail($id);
            $health_entity->restore();

            return $this->successResponse(
                new HealthEntityResource($health_entity),
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
