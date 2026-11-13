<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\WaistbandType\WaistbandTypeFindRequest;
use App\Http\Requests\Typification\WaistbandType\WaistbandTypeAllRequest;
use App\Http\Requests\Typification\WaistbandType\WaistbandTypeDeleteRequest;
use App\Http\Requests\Typification\WaistbandType\WaistbandTypeRestoreRequest;
use App\Http\Requests\Typification\WaistbandType\WaistbandTypeStoreRequest;
use App\Http\Requests\Typification\WaistbandType\WaistbandTypeUpdateRequest;
use App\Http\Resources\Typification\WaistbandType\WaistbandTypeCollection;
use App\Http\Resources\Typification\WaistbandType\WaistbandTypeResource;
use App\Models\WaistbandType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="WaistbandTypeSettings",
 *     type="object",
 *     required={"code"},
 *     description="Configuración del tipo de pretina. Se guarda tal cual llega (reemplaza la anterior).",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos.", example="1003")
 * )
 *
 * @OA\Schema(
 *     schema="WaistbandType",
 *     type="object",
 *     description="Tipo de pretina del jean (1 BOTÓN, 2 BOTÓN…).",
 *     @OA\Property(property="id", type="integer", example=504),
 *     @OA\Property(property="item_id", type="integer", example=21),
 *     @OA\Property(property="name", type="string", example="ELÁSTICA"),
 *     @OA\Property(property="description", type="string", example="PRETINA CON RESORTE INTERNO"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/WaistbandTypeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 09:50:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 09:50:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=21),
 *         @OA\Property(property="name", type="string", example="Tipos de Pretina"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de pretina."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="WaistbandTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="waistband_type", ref="#/components/schemas/WaistbandType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="WaistbandTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="waistband_types", type="array", @OA\Items(ref="#/components/schemas/WaistbandType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="WaistbandTypeRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de pretina.", example="ELÁSTICA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="PRETINA CON RESORTE INTERNO"),
 *     @OA\Property(property="settings", ref="#/components/schemas/WaistbandTypeSettings")
 * )
 */
class WaistbandTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/waistband_types/all",
     *     tags={"Typification › Waistband Types"},
     *     summary="Listar los tipos de pretina",
     *     description="También la pueden consultar quienes configuran procesos, subprocesos u operaciones.",
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
     *         description="Lista de tipos de pretina cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WaistbandTypeListResponse")
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
    public function all(WaistbandTypeAllRequest $request)
    {
        try {
            $waistband_types = WaistbandType::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $waistband_types = $waistband_types->paginate($request->integer('per_page', $waistband_types->count()));

            return $this->successResponse(
                new WaistbandTypeCollection($waistband_types),
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
     *     path="/typification/waistband_types/find/{id}",
     *     tags={"Typification › Waistband Types"},
     *     summary="Obtener un tipo de pretina",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de pretina cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WaistbandTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(WaistbandTypeFindRequest $request, $id)
    {
        try {
            $waistband_type = WaistbandType::findOrFail($id);

            return $this->successResponse(
                new WaistbandTypeResource($waistband_type),
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
     *     path="/typification/waistband_types/store",
     *     tags={"Typification › Waistband Types"},
     *     summary="Crear un tipo de pretina",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/WaistbandTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de pretina creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WaistbandTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"settings.code": {"Debe tener entre 2 y 5 dígitos."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(WaistbandTypeStoreRequest $request)
    {
        try {
            $waistband_type = new WaistbandType();
            $waistband_type->name = $request->input('name');
            $waistband_type->description = $request->input('description');
            $waistband_type->settings = $request->input('settings');

            $waistband_type->save();

            return $this->successResponse(
                new WaistbandTypeResource($waistband_type),
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
     *     path="/typification/waistband_types/update/{id}",
     *     tags={"Typification › Waistband Types"},
     *     summary="Editar un tipo de pretina",
     *     description="Reemplaza nombre, descripción y `settings` completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/WaistbandTypeRequest",
     *             example={"name": "ELÁSTICA CON CORDÓN", "description": "PRETINA CON RESORTE Y CORDÓN AJUSTABLE", "settings": {"code": "1003"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de pretina editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WaistbandTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de pretina no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de pretina", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(WaistbandTypeUpdateRequest $request, $id)
    {
        try {
            $waistband_type = WaistbandType::findOrFail($id);
            $waistband_type->name = $request->input('name');
            $waistband_type->description = $request->input('description');
            $waistband_type->settings = $request->input('settings');

            $waistband_type->save();

            return $this->successResponse(
                new WaistbandTypeResource($waistband_type),
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
     *     path="/typification/waistband_types/delete/{id}",
     *     tags={"Typification › Waistband Types"},
     *     summary="Desactivar un tipo de pretina",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de pretina desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/WaistbandTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(WaistbandTypeDeleteRequest $request, $id)
    {
        try {
            $waistband_type = WaistbandType::findOrFail($id);
            $waistband_type->delete();

            return $this->successResponse(
                new WaistbandTypeResource($waistband_type),
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
     *     path="/typification/waistband_types/restore/{id}",
     *     tags={"Typification › Waistband Types"},
     *     summary="Restaurar un tipo de pretina desactivado",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de pretina restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WaistbandTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(WaistbandTypeRestoreRequest $request, $id)
    {
        try {
            $waistband_type = WaistbandType::withTrashed()->findOrFail($id);
            $waistband_type->restore();

            return $this->successResponse(
                new WaistbandTypeResource($waistband_type),
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
