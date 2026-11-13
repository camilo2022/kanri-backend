<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationalStructure\Area\AreaAllRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaDeleteRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaFindRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaRestoreRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaStoreRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaUpdateRequest;
use App\Http\Resources\OrganizationalStructure\Area\AreaCollection;
use App\Http\Resources\OrganizationalStructure\Area\AreaResource;
use App\Models\Area;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="Area",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=7),
 *     @OA\Property(property="item_id", type="integer", example=3),
 *     @OA\Property(property="name", type="string", example="CONTROL DE CALIDAD"),
 *     @OA\Property(property="description", type="string", example="ÁREA ENCARGADA DE INSPECCIONAR LAS PRENDAS TERMINADAS"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 09:32:10"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 09:32:10"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=3),
 *         @OA\Property(property="name", type="string", example="Áreas"),
 *         @OA\Property(property="description", type="string", example="Listado de áreas disponibles en la organización."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(
 *         property="positions",
 *         type="array",
 *         description="Cargos asociados al área.",
 *         @OA\Items(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/Subitem"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="pivot",
 *                         type="object",
 *                         @OA\Property(property="model_type", type="string", example="App\Models\Position"),
 *                         @OA\Property(property="subitem_id", type="integer", example=7),
 *                         @OA\Property(property="model_id", type="integer", example=58)
 *                     )
 *                 )
 *             }
 *         ),
 *         example={{"id": 58, "item_id": 4, "name": "INSPECTOR DE CALIDAD", "description": "CARGO QUE INSPECCIONA LAS PRENDAS TERMINADAS", "settings": {}, "created_at": "2026-05-14T09:40:00.000000Z", "updated_at": "2026-05-14T09:40:00.000000Z", "deleted_at": null, "pivot": {"model_type": "App\Models\Position", "subitem_id": 7, "model_id": 58}}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AreaResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="area", ref="#/components/schemas/Area")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="AreaListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="areas", type="array", @OA\Items(ref="#/components/schemas/Area")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="AreaRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         minLength=4,
 *         maxLength=50,
 *         description="Nombre en MAYÚSCULAS. Único entre las áreas.",
 *         example="CONTROL DE CALIDAD"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         maxLength=255,
 *         description="Descripción en MAYÚSCULAS.",
 *         example="ÁREA ENCARGADA DE INSPECCIONAR LAS PRENDAS TERMINADAS"
 *     )
 * )
 */
class AreaController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/organizational_structure/areas/all",
     *     tags={"Organizational Structure › Areas"},
     *     summary="Listar áreas",
     *     description="Cada área trae sus cargos (`positions`). En esta lista no viene `settings`.",
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
     *     @OA\Parameter(ref="#/components/parameters/WithTrashed"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de áreas cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AreaListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"with_trashed": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(AreaAllRequest $request)
    {
        try {
            $areas = Area::with(['item', 'positions'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $areas = $areas->paginate($request->integer('per_page', $areas->count()));

            return $this->successResponse(
                new AreaCollection($areas),
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
     *     path="/organizational_structure/areas/find/{id}",
     *     tags={"Organizational Structure › Areas"},
     *     summary="Obtener un área",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Área cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AreaResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(AreaFindRequest $request, $id)
    {
        try {
            $area = Area::with(['item', 'positions'])
                ->findOrFail($id);

            return $this->successResponse(
                new AreaResource($area),
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
     *     path="/organizational_structure/areas/store",
     *     tags={"Organizational Structure › Areas"},
     *     summary="Crear un área",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AreaRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Área creada correctamente. `positions` llega vacío.",
     *         @OA\JsonContent(ref="#/components/schemas/AreaResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"El campo debe estar en mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(AreaStoreRequest $request)
    {
        try {
            $area = new Area();
            $area->name = $request->input('name');
            $area->description = $request->input('description');
            $area->save();

            return $this->successResponse(
                new AreaResource($area),
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
     *     path="/organizational_structure/areas/update/{id}",
     *     tags={"Organizational Structure › Areas"},
     *     summary="Editar un área",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/AreaRequest",
     *             example={"name": "PLANEACIÓN DE PRODUCCIÓN", "description": "ÁREA QUE PROGRAMA LAS ÓRDENES DE PRODUCCIÓN"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Área editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AreaResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o área no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del área", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(AreaUpdateRequest $request, $id)
    {
        try {
            $area = Area::findOrFail($id);
            $area->name = $request->input('name');
            $area->description = $request->input('description');
            $area->save();

            return $this->successResponse(
                new AreaResource($area),
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
     *     path="/organizational_structure/areas/delete/{id}",
     *     tags={"Organizational Structure › Areas"},
     *     summary="Desactivar un área",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Área desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/AreaResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(AreaDeleteRequest $request, $id)
    {
        try {
            $area = Area::findOrFail($id);
            $area->delete();

            return $this->successResponse(
                new AreaResource($area),
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
     *     path="/organizational_structure/areas/restore/{id}",
     *     tags={"Organizational Structure › Areas"},
     *     summary="Restaurar un área desactivada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Área restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AreaResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(AreaRestoreRequest $request, $id)
    {
        try {
            $area = Area::withTrashed()->findOrFail($id);
            $area->restore();

            return $this->successResponse(
                new AreaResource($area),
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
