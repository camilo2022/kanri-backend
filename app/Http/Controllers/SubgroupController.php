<?php

namespace App\Http\Controllers;

use App\Http\Requests\Classification\Subgroup\SubgroupAllRequest;
use App\Http\Requests\Classification\Subgroup\SubgroupDeleteRequest;
use App\Http\Requests\Classification\Subgroup\SubgroupFindRequest;
use App\Http\Requests\Classification\Subgroup\SubgroupRestoreRequest;
use App\Http\Requests\Classification\Subgroup\SubgroupStoreRequest;
use App\Http\Requests\Classification\Subgroup\SubgroupUpdateRequest;
use App\Http\Resources\Classification\Subgroup\SubgroupCollection;
use App\Http\Resources\Classification\Subgroup\SubgroupResource;
use App\Models\Subgroup;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="Subgroup",
 *     type="object",
 *     description="Subgrupo de clasificación del producto (TIRO ALTO, PRETINA ALTA…). No depende de un grupo.",
 *     @OA\Property(property="id", type="integer", example=340),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=14),
 *     @OA\Property(property="name", type="string", example="TIRO BAJO"),
 *     @OA\Property(property="description", type="string", example="JEAN DE TIRO BAJO"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 15:05:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 15:05:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=14),
 *         @OA\Property(property="name", type="string", example="Subgrupos"),
 *         @OA\Property(property="description", type="string", example="Listado de subgrupos."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SubgroupResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="subgroup", ref="#/components/schemas/Subgroup")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SubgroupListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="subgroups", type="array", @OA\Items(ref="#/components/schemas/Subgroup")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SubgroupRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los subgrupos.", example="TIRO BAJO"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="JEAN DE TIRO BAJO")
 * )
 */
class SubgroupController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/classification/subgroups/all",
     *     tags={"Classification › Subgroups"},
     *     summary="Listar los subgrupos",
     *     description="También la pueden consultar quienes configuran procesos, subprocesos u operaciones. En esta lista no vienen `item_id` ni `settings`.",
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
     *         description="Lista de subgrupos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubgroupListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"dir": {"Valor inválido. Permitidos: asc, desc."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(SubgroupAllRequest $request)
    {
        try {
            $subgroups = Subgroup::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $subgroups = $subgroups->paginate($request->integer('per_page', $subgroups->count()));

            return $this->successResponse(
                new SubgroupCollection($subgroups),
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
     *     path="/classification/subgroups/find/{id}",
     *     tags={"Classification › Subgroups"},
     *     summary="Obtener un subgrupo",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subgrupo cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubgroupResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(SubgroupFindRequest $request, $id)
    {
        try {
            $subgroup = Subgroup::findOrFail($id);

            return $this->successResponse(
                new SubgroupResource($subgroup),
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
     *     path="/classification/subgroups/store",
     *     tags={"Classification › Subgroups"},
     *     summary="Crear un subgrupo",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SubgroupRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Subgrupo creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubgroupResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Se permite mínimo 4 caracteres."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(SubgroupStoreRequest $request)
    {
        try {
            $subgroup = new Subgroup();
            $subgroup->name = $request->input('name');
            $subgroup->description = $request->input('description');
            $subgroup->save();

            return $this->successResponse(
                new SubgroupResource($subgroup),
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
     *     path="/classification/subgroups/update/{id}",
     *     tags={"Classification › Subgroups"},
     *     summary="Editar un subgrupo",
     *     description="Reemplaza nombre y descripción.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/SubgroupRequest",
     *             example={"name": "PRETINA MEDIA", "description": "PRETINA DE ALTURA MEDIA"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subgrupo editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubgroupResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o subgrupo no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del subgrupo", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(SubgroupUpdateRequest $request, $id)
    {
        try {
            $subgroup = Subgroup::findOrFail($id);
            $subgroup->name = $request->input('name');
            $subgroup->description = $request->input('description');
            $subgroup->save();

            return $this->successResponse(
                new SubgroupResource($subgroup),
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
     *     path="/classification/subgroups/delete/{id}",
     *     tags={"Classification › Subgroups"},
     *     summary="Desactivar un subgrupo",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subgrupo desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/SubgroupResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(SubgroupDeleteRequest $request, $id)
    {
        try {
            $subgroup = Subgroup::findOrFail($id);
            $subgroup->delete();

            return $this->successResponse(
                new SubgroupResource($subgroup),
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
     *     path="/classification/subgroups/restore/{id}",
     *     tags={"Classification › Subgroups"},
     *     summary="Restaurar un subgrupo desactivado",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subgrupo restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubgroupResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(SubgroupRestoreRequest $request, $id)
    {
        try {
            $subgroup = Subgroup::withTrashed()->findOrFail($id);
            $subgroup->restore();

            return $this->successResponse(
                new SubgroupResource($subgroup),
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
