<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\StopType\Stop\StopAllRequest;
use App\Http\Requests\Typification\StopType\Stop\StopDeleteRequest;
use App\Http\Requests\Typification\StopType\Stop\StopFindRequest;
use App\Http\Requests\Typification\StopType\Stop\StopRestoreRequest;
use App\Http\Requests\Typification\StopType\Stop\StopStoreRequest;
use App\Http\Requests\Typification\StopType\Stop\StopUpdateRequest;
use App\Http\Resources\Typification\StopType\Stop\StopCollection;
use App\Http\Resources\Typification\StopType\Stop\StopResource;
use App\Models\Stop;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="StopSettings",
 *     type="object",
 *     description="Configuración de la parada. Opcional; se guarda tal cual llega (reemplaza la anterior). Las paradas no programadas hoy tienen `[]`.",
 *     @OA\Property(property="range_time", type="string", description="Duración de la parada, mayor o igual a 1. Por los datos actuales, en minutos (DESAYUNO 15, ALMUERZO 30). Se usa en las paradas de tipo programada.", example="10")
 * )
 *
 * @OA\Schema(
 *     schema="Stop",
 *     type="object",
 *     description="Parada de producción (almuerzo, pausa activa, paro mecánico…). Pertenece a un tipo de parada por `model_has_subitems`.",
 *     @OA\Property(property="id", type="integer", example=483),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=43),
 *     @OA\Property(property="name", type="string", example="REUNIÓN DE ÁREA"),
 *     @OA\Property(property="description", type="string", example="REUNIÓN DIARIA DEL EQUIPO DE CONFECCIÓN"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/StopSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 14:30:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 14:30:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=43),
 *         @OA\Property(property="name", type="string", example="Parada"),
 *         @OA\Property(property="description", type="string", example="Listado paradas"),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StopResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="stop", allOf={
 *         @OA\Schema(ref="#/components/schemas/Stop"),
 *         @OA\Schema(@OA\Property(property="stop_type", type="array", description="Tipo de parada (lista con uno).", @OA\Items(ref="#/components/schemas/Subitem")))
 *     })),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="StopListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="stops", type="array", @OA\Items(ref="#/components/schemas/Stop")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="StopRequest",
 *     type="object",
 *     required={"stop_type_id", "name", "description"},
 *     @OA\Property(property="stop_type_id", type="integer", description="Tipo de parada. Solo se usa al crear (al editar se ignora).", example=473),
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre las paradas.", example="REUNIÓN DE ÁREA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="REUNIÓN DIARIA DEL EQUIPO DE CONFECCIÓN"),
 *     @OA\Property(property="settings", ref="#/components/schemas/StopSettings")
 * )
 */
class StopController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/stop_types/stops/all/{file_type_id}",
     *     tags={"Typification › Stops"},
     *     summary="Listar las paradas de un tipo de parada",
     *     description="Cada parada trae su `item` y `settings`; no trae `item_id` ni el tipo de parada.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="file_type_id",
     *         in="path",
     *         required=true,
     *         description="Identificador del **tipo de parada** (el nombre del parámetro en la ruta está copiado de tipos de archivo). No se valida: un id que no existe devuelve la lista vacía.",
     *         @OA\Schema(type="integer", example=473)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="**Hoy responde 500**: la búsqueda usa `ILIKE`, que MySQL no admite (ver pendientes). Debería buscar cada palabra en nombre y descripción.",
     *         @OA\Schema(type="string")
     *     ),
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
     *         description="Lista de paradas cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopListResponse")
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
    public function all(StopAllRequest $request, $stop_type_id)
    {
        try {
            $stops = Stop::with(['stop_type'])
                ->whereHas('stop_type', fn ($r) => $r->where('id', $stop_type_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $stops = $stops->paginate($request->integer('per_page', $stops->count()));

            return $this->successResponse(
                new StopCollection($stops),
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
     *     path="/typification/stop_types/stops/find/{id}",
     *     tags={"Typification › Stops"},
     *     summary="Obtener una parada",
     *     description="Trae la parada con su tipo de parada (`stop_type`, lista).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Parada cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(StopFindRequest $request, $id)
    {
        try {
            $stop = Stop::with(['stop_type'])->findOrFail($id);

            return $this->successResponse(
                new StopResource($stop),
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
     *     path="/typification/stop_types/stops/store",
     *     tags={"Typification › Stops"},
     *     summary="Crear una parada",
     *     description="Crea la parada y la asocia al tipo de parada indicado.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StopRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Parada creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"stop_type_id": "Identificador del tipo de archivo", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"stop_type_id": {"Es obligatorio."}, "name": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(StopStoreRequest $request)
    {
        try {
            $stop = new Stop;
            $stop->name = $request->input('name');
            $stop->description = $request->input('description');
            $stop->settings = $request->input('settings');
            $stop->save();

            $stop->auditSync('stop_type', $request->integer('stop_type_id'));

            return $this->successResponse(
                new StopResource($stop),
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
     *     path="/typification/stop_types/stops/update/{id}",
     *     tags={"Typification › Stops"},
     *     summary="Editar una parada",
     *     description="Reemplaza nombre, descripción y `settings`; no cambia el tipo de parada. Si no se envía `settings`, queda en `null`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/StopRequest",
     *             example={"name": "REUNIÓN DE ÁREA", "description": "REUNIÓN SEMANAL DEL EQUIPO DE CONFECCIÓN", "settings": {"range_time": "20"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Parada editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o parada no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del subtipo de arhivo", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(StopUpdateRequest $request, $id)
    {
        try {
            $stop = Stop::findOrFail($id);
            $stop->name = $request->input('name');
            $stop->description = $request->input('description');
            $stop->settings = $request->input('settings');
            $stop->save();

            return $this->successResponse(
                new StopResource($stop),
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
     *     path="/typification/stop_types/stops/delete/{id}",
     *     tags={"Typification › Stops"},
     *     summary="Desactivar una parada",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Parada desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/StopResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(StopDeleteRequest $request, $id)
    {
        try {
            $stop = Stop::findOrFail($id);
            $stop->delete();

            return $this->successResponse(
                new StopResource($stop),
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
     *     path="/typification/stop_types/stops/restore/{id}",
     *     tags={"Typification › Stops"},
     *     summary="Restaurar una parada desactivada",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Parada restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/StopResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(StopRestoreRequest $request, $id)
    {
        try {
            $stop = Stop::withTrashed()->findOrFail($id);
            $stop->restore();

            return $this->successResponse(
                new StopResource($stop),
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
