<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompensationFund\CompensationFundAllRequest;
use App\Http\Requests\CompensationFund\CompensationFundDeleteRequest;
use App\Http\Requests\CompensationFund\CompensationFundFindRequest;
use App\Http\Requests\CompensationFund\CompensationFundRestoreRequest;
use App\Http\Requests\CompensationFund\CompensationFundStoreRequest;
use App\Http\Requests\CompensationFund\CompensationFundUpdateRequest;
use App\Http\Resources\CompensationFund\CompensationFundCollection;
use App\Http\Resources\CompensationFund\CompensationFundResource;
use App\Models\CompensationFund;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Compensation Funds",
 *     description="Endpoints para gestionar cajas de compensación"
 * )
 *
 * @OA\Schema(
 *     schema="CompensationFund",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=163),
 *     @OA\Property(property="item_id", type="integer", example=10),
 *     @OA\Property(property="name", type="string", example="CAFAM"),
 *     @OA\Property(property="description", type="string", example="CAJA DE COMPENSACION FAMILIAR CAFAM"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 13:20:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 13:20:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=10),
 *         @OA\Property(property="name", type="string", example="Cajas de compensación"),
 *         @OA\Property(property="description", type="string", example="Listado de cajas de compensación."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CompensationFundResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="compensation_fund", ref="#/components/schemas/CompensationFund")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="CompensationFundListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="compensation_funds", type="array", @OA\Items(ref="#/components/schemas/CompensationFund")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="CompensationFundRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=100, description="Nombre en MAYÚSCULAS. Único entre las cajas de compensación.", example="CAFAM"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="CAJA DE COMPENSACION FAMILIAR CAFAM")
 * )
 */
class CompensationFundController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/compensation_funds/all",
     *     tags={"Compensation Funds"},
     *     summary="Listar las cajas de compensación",
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
     *         description="Lista de cajas de compensación cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CompensationFundListResponse")
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
    public function all(CompensationFundAllRequest $request)
    {
        try {
            $compensation_funds = CompensationFund::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $compensation_funds = $compensation_funds->paginate($request->integer('per_page', $compensation_funds->count()));

            return $this->successResponse(
                new CompensationFundCollection($compensation_funds),
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
     *     path="/compensation_funds/find/{id}",
     *     tags={"Compensation Funds"},
     *     summary="Obtener una caja de compensación",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Caja de compensación cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CompensationFundResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(CompensationFundFindRequest $request, $id)
    {
        try {
            $compensation_fund = CompensationFund::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new CompensationFundResource($compensation_fund),
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
     *     path="/compensation_funds/store",
     *     tags={"Compensation Funds"},
     *     summary="Crear una caja de compensación",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CompensationFundRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Caja de compensación creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CompensationFundResponse")
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
    public function store(CompensationFundStoreRequest $request)
    {
        try {
            $compensation_fund = new CompensationFund();
            $compensation_fund->name = $request->input('name');
            $compensation_fund->description = $request->input('description');
            $compensation_fund->save();

            return $this->successResponse(
                new CompensationFundResource($compensation_fund),
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
     *     path="/compensation_funds/update/{id}",
     *     tags={"Compensation Funds"},
     *     summary="Editar una caja de compensación",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/CompensationFundRequest",
     *             example={"name": "COMPENSAR", "description": "CAJA DE COMPENSACION FAMILIAR COMPENSAR"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Caja de compensación editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CompensationFundResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o caja de compensación no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador de la caja de compensación", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"description": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(CompensationFundUpdateRequest $request, $id)
    {
        try {
            $compensation_fund = CompensationFund::findOrFail($id);
            $compensation_fund->name = $request->input('name');
            $compensation_fund->description = $request->input('description');
            $compensation_fund->save();

            return $this->successResponse(
                new CompensationFundResource($compensation_fund),
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
     *     path="/compensation_funds/delete/{id}",
     *     tags={"Compensation Funds"},
     *     summary="Desactivar una caja de compensación",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Caja de compensación desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/CompensationFundResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(CompensationFundDeleteRequest $request, $id)
    {
        try {
            $compensation_fund = CompensationFund::findOrFail($id);
            $compensation_fund->delete();

            return $this->successResponse(
                new CompensationFundResource($compensation_fund),
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
     *     path="/compensation_funds/restore/{id}",
     *     tags={"Compensation Funds"},
     *     summary="Restaurar una caja de compensación desactivada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Caja de compensación restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CompensationFundResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(CompensationFundRestoreRequest $request, $id)
    {
        try {
            $compensation_fund = CompensationFund::withTrashed()->findOrFail($id);
            $compensation_fund->restore();

            return $this->successResponse(
                new CompensationFundResource($compensation_fund),
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
