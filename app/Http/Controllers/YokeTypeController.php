<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\YokeType\YokeTypeAllRequest;
use App\Http\Requests\Typification\YokeType\YokeTypeDeleteRequest;
use App\Http\Requests\Typification\YokeType\YokeTypeFindRequest;
use App\Http\Requests\Typification\YokeType\YokeTypeRestoreRequest;
use App\Http\Requests\Typification\YokeType\YokeTypeStoreRequest;
use App\Http\Requests\Typification\YokeType\YokeTypeUpdateRequest;
use App\Http\Resources\Typification\YokeType\YokeTypeCollection;
use App\Http\Resources\Typification\YokeType\YokeTypeResource;
use App\Models\YokeType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="YokeTypeSettings",
 *     type="object",
 *     required={"code"},
 *     description="Configuración del tipo de cotilla. Se guarda tal cual llega (reemplaza la anterior).",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos.", example="1003")
 * )
 *
 * @OA\Schema(
 *     schema="YokeType",
 *     type="object",
 *     description="Tipo de cotilla del jean (CERRADORA, DOBLE COTILLA, SOBREPUESTA…).",
 *     @OA\Property(property="id", type="integer", example=503),
 *     @OA\Property(property="item_id", type="integer", example=19),
 *     @OA\Property(property="name", type="string", example="COTILLA EN V"),
 *     @OA\Property(property="description", type="string", example="COTILLA CON CORTE EN FORMA DE V"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/YokeTypeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 10:35:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 10:35:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=19),
 *         @OA\Property(property="name", type="string", example="Tipos de Cotilla"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de cotilla."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="YokeTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="yoke_type", ref="#/components/schemas/YokeType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="YokeTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="yoke_types", type="array", @OA\Items(ref="#/components/schemas/YokeType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="YokeTypeRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de cotilla.", example="COTILLA EN V"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="COTILLA CON CORTE EN FORMA DE V"),
 *     @OA\Property(property="settings", ref="#/components/schemas/YokeTypeSettings")
 * )
 */
class YokeTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/yoke_types/all",
     *     tags={"Typification › Yoke Types"},
     *     summary="Listar los tipos de cotilla",
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
     *         description="Lista de tipos de cotilla cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/YokeTypeListResponse")
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
    public function all(YokeTypeAllRequest $request)
    {
        try {
            $yoke_types = YokeType::with(['item'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $yoke_types = $yoke_types->paginate($request->integer('per_page', $yoke_types->count()));

            return $this->successResponse(
                new YokeTypeCollection($yoke_types),
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
     *     path="/typification/yoke_types/find/{id}",
     *     tags={"Typification › Yoke Types"},
     *     summary="Obtener un tipo de cotilla",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de cotilla cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/YokeTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(YokeTypeFindRequest $request, $id)
    {
        try {
            $yoke_type = YokeType::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new YokeTypeResource($yoke_type),
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
     *     path="/typification/yoke_types/store",
     *     tags={"Typification › Yoke Types"},
     *     summary="Crear un tipo de cotilla",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/YokeTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de cotilla creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/YokeTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"name": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(YokeTypeStoreRequest $request)
    {
        try {
            $yoke_type = new YokeType();
            $yoke_type->name = $request->input('name');
            $yoke_type->description = $request->input('description');
            $yoke_type->settings = $request->input('settings');

            $yoke_type->save();

            return $this->successResponse(
                new YokeTypeResource($yoke_type),
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
     *     path="/typification/yoke_types/update/{id}",
     *     tags={"Typification › Yoke Types"},
     *     summary="Editar un tipo de cotilla",
     *     description="Reemplaza nombre, descripción y `settings` completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/YokeTypeRequest",
     *             example={"name": "COTILLA EN V", "description": "COTILLA EN V CON DOBLE PESPUNTE", "settings": {"code": "1003"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de cotilla editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/YokeTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de cotilla no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de cotilla", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(YokeTypeUpdateRequest $request, $id)
    {
        try {
            $yoke_type = YokeType::findOrFail($id);
            $yoke_type->name = $request->input('name');
            $yoke_type->description = $request->input('description');
            $yoke_type->settings = $request->input('settings');

            $yoke_type->save();

            return $this->successResponse(
                new YokeTypeResource($yoke_type),
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
     * @OA\Delete(
     *     path="/typification/yoke_types/delete/{id}",
     *     tags={"Typification › Yoke Types"},
     *     summary="Desactivar un tipo de cotilla",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de cotilla desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/YokeTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(YokeTypeDeleteRequest $request, $id)
    {
        try {
            $yoke_type = YokeType::findOrFail($id);
            $yoke_type->delete();

            return $this->successResponse(
                new YokeTypeResource($yoke_type),
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
     *     path="/typification/yoke_types/restore/{id}",
     *     tags={"Typification › Yoke Types"},
     *     summary="Restaurar un tipo de cotilla desactivado",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de cotilla restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/YokeTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(YokeTypeRestoreRequest $request, $id)
    {
        try {
            $yoke_type = YokeType::withTrashed()->findOrFail($id);
            $yoke_type->restore();

            return $this->successResponse(
                new YokeTypeResource($yoke_type),
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
