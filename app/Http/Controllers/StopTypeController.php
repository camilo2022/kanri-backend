<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\StopType\StopTypeAllRequest;
use App\Http\Requests\Typification\StopType\StopTypeDeleteRequest;
use App\Http\Requests\Typification\StopType\StopTypeFindRequest;
use App\Http\Requests\Typification\StopType\StopTypeRestoreRequest;
use App\Http\Requests\Typification\StopType\StopTypeStoreRequest;
use App\Http\Requests\Typification\StopType\StopTypeUpdateRequest;
use App\Http\Resources\Typification\StopType\StopTypeCollection;
use App\Http\Resources\Typification\StopType\StopTypeResource;
use App\Models\StopType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="StopTypeSettings",
 *     type="object",
 *     required={"has_defined_time_range"},
 *     description="Configuración del tipo de parada. Se guarda tal cual llega (reemplaza la anterior).",
 *     @OA\Property(property="has_defined_time_range", type="boolean", description="Sus paradas tienen una duración definida (`settings.range_time` de la parada).", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="StopType",
 *     type="object",
 *     description="Tipo de parada de producción. Hoy: PROGRAMADA (paradas con duración fija, como el almuerzo) y NO PROGRAMADA (imprevistos, como un paro mecánico).",
 *     @OA\Property(property="id", type="integer", example=482),
 *     @OA\Property(property="item_id", type="integer", example=42),
 *     @OA\Property(property="name", type="string", example="MANTENIMIENTO"),
 *     @OA\Property(property="description", type="string", example="PARADAS POR MANTENIMIENTO PREVENTIVO DE MÁQUINAS"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/StopTypeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 14:10:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 14:10:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=42),
 *         @OA\Property(property="name", type="string", example="Tipos de Parada"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de parada"),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StopTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="stop_type", ref="#/components/schemas/StopType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="StopTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="stop_types", type="array", @OA\Items(ref="#/components/schemas/StopType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="StopTypeRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de parada.", example="MANTENIMIENTO"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="PARADAS POR MANTENIMIENTO PREVENTIVO DE MÁQUINAS"),
 *     @OA\Property(property="settings", ref="#/components/schemas/StopTypeSettings")
 * )
 */
class StopTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/stop_types/all",
     *     tags={"Typification › Stop Types"},
     *     summary="Listar los tipos de parada",
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
     *         description="Lista de tipos de parada cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopTypeListResponse")
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
    public function all(StopTypeAllRequest $request)
    {
        try {
            $stop_types = StopType::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $stop_types = $stop_types->paginate($request->integer('per_page', $stop_types->count()));

            return $this->successResponse(
                new StopTypeCollection($stop_types),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/typification/stop_types/find/{id}",
     *     tags={"Typification › Stop Types"},
     *     summary="Obtener un tipo de parada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de parada cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(StopTypeFindRequest $request, $id)
    {
        try {
            $stop_type = StopType::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new StopTypeResource($stop_type),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/typification/stop_types/store",
     *     tags={"Typification › Stop Types"},
     *     summary="Crear un tipo de parada",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StopTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de parada creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings.has_defined_time_range": "Tiene rango de tiempo definido"},
     *                 "errors": {"settings.has_defined_time_range": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(StopTypeStoreRequest $request)
    {
        try {
            $stop_type = new StopType;
            $stop_type->name = $request->input('name');
            $stop_type->description = $request->input('description');
            $stop_type->settings = $request->input('settings');

            $stop_type->save();

            return $this->successResponse(
                new StopTypeResource($stop_type),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/typification/stop_types/update/{id}",
     *     tags={"Typification › Stop Types"},
     *     summary="Editar un tipo de parada",
     *     description="Reemplaza nombre, descripción y `settings` completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/StopTypeRequest",
     *             example={"name": "MANTENIMIENTO CORRECTIVO", "description": "PARADAS PARA REPARAR UNA MÁQUINA", "settings": {"has_defined_time_range": false}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de parada editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de parada no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de parada", "name": "Nombre", "description": "Descripción", "settings.has_defined_time_range": "Tiene rango de tiempo definido"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(StopTypeUpdateRequest $request, $id)
    {
        try {
            $stop_type = StopType::findOrFail($id);
            $stop_type->name = $request->input('name');
            $stop_type->description = $request->input('description');
            $stop_type->settings = $request->input('settings');

            $stop_type->save();

            return $this->successResponse(
                new StopTypeResource($stop_type),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/typification/stop_types/delete/{id}",
     *     tags={"Typification › Stop Types"},
     *     summary="Desactivar un tipo de parada",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de parada desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/StopTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(StopTypeDeleteRequest $request, $id)
    {
        try {
            $stop_type = StopType::findOrFail($id);
            $stop_type->delete();

            return $this->successResponse(
                new StopTypeResource($stop_type),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Patch(
     *     path="/typification/stop_types/restore/{id}",
     *     tags={"Typification › Stop Types"},
     *     summary="Restaurar un tipo de parada desactivado",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de parada restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(StopTypeRestoreRequest $request, $id)
    {
        try {
            $stop_type = StopType::withTrashed()->findOrFail($id);
            $stop_type->restore();

            return $this->successResponse(
                new StopTypeResource($stop_type),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
