<?php

namespace App\Http\Controllers;

use App\Http\Requests\PensionFund\PensionFundAllRequest;
use App\Http\Requests\PensionFund\PensionFundDeleteRequest;
use App\Http\Requests\PensionFund\PensionFundFindRequest;
use App\Http\Requests\PensionFund\PensionFundRestoreRequest;
use App\Http\Requests\PensionFund\PensionFundStoreRequest;
use App\Http\Requests\PensionFund\PensionFundUpdateRequest;
use App\Http\Resources\PensionFund\PensionFundCollection;
use App\Http\Resources\PensionFund\PensionFundResource;
use App\Models\PensionFund;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Pension Funds",
 *     description="Endpoints para gestionar fondos de pensión"
 * )
 *
 * @OA\Schema(
 *     schema="PensionFund",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=118),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=9),
 *     @OA\Property(property="name", type="string", example="SKANDIA"),
 *     @OA\Property(property="description", type="string", example="SKANDIA PENSIONES Y CESANTÍAS S.A."),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 17:20:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 17:20:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=9),
 *         @OA\Property(property="name", type="string", example="Fondos de pensión"),
 *         @OA\Property(property="description", type="string", example="Listado de fondos de pensión."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PensionFundResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="pension_fund", ref="#/components/schemas/PensionFund")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PensionFundListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="pension_funds", type="array", @OA\Items(ref="#/components/schemas/PensionFund")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PensionFundRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=100, description="Nombre en MAYÚSCULAS. Único entre los fondos de pensión.", example="SKANDIA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="SKANDIA PENSIONES Y CESANTÍAS S.A.")
 * )
 */
class PensionFundController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/pension_funds/all",
     *     tags={"Pension Funds"},
     *     summary="Listar los fondos de pensión",
     *     description="En esta lista no vienen `item_id` ni `settings`.",
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
     *         description="Lista de fondos de pensión cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PensionFundListResponse")
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
    public function all(PensionFundAllRequest $request)
    {
        try {
            $pension_funds = PensionFund::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $pension_funds = $pension_funds->paginate($request->integer('per_page', $pension_funds->count()));

            return $this->successResponse(
                new PensionFundCollection($pension_funds),
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
     *     path="/pension_funds/find/{id}",
     *     tags={"Pension Funds"},
     *     summary="Obtener un fondo de pensión",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Fondo de pensión cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PensionFundResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(PensionFundFindRequest $request, $id)
    {
        try {
            $pension_fund = PensionFund::findOrFail($id);

            return $this->successResponse(
                new PensionFundResource($pension_fund),
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
     *     path="/pension_funds/store",
     *     tags={"Pension Funds"},
     *     summary="Crear un fondo de pensión",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PensionFundRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Fondo de pensión creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PensionFundResponse")
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
    public function store(PensionFundStoreRequest $request)
    {
        try {
            $pension_fund = new PensionFund();
            $pension_fund->name = $request->input('name');
            $pension_fund->description = $request->input('description');
            $pension_fund->save();

            return $this->successResponse(
                new PensionFundResource($pension_fund),
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
     *     path="/pension_funds/update/{id}",
     *     tags={"Pension Funds"},
     *     summary="Editar un fondo de pensión",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/PensionFundRequest",
     *             example={"name": "SKANDIA PENSIONES", "description": "SKANDIA PENSIONES Y CESANTÍAS S.A."}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fondo de pensión editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PensionFundResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o fondo de pensión no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del fondo de pensión", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"description": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(PensionFundUpdateRequest $request, $id)
    {
        try {
            $pension_fund = PensionFund::findOrFail($id);
            $pension_fund->name = $request->input('name');
            $pension_fund->description = $request->input('description');
            $pension_fund->save();

            return $this->successResponse(
                new PensionFundResource($pension_fund),
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
     *     path="/pension_funds/delete/{id}",
     *     tags={"Pension Funds"},
     *     summary="Desactivar un fondo de pensión",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Fondo de pensión desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/PensionFundResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(PensionFundDeleteRequest $request, $id)
    {
        try {
            $pension_fund = PensionFund::findOrFail($id);
            $pension_fund->delete();

            return $this->successResponse(
                new PensionFundResource($pension_fund),
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
     *     path="/pension_funds/restore/{id}",
     *     tags={"Pension Funds"},
     *     summary="Restaurar un fondo de pensión desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Fondo de pensión restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PensionFundResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(PensionFundRestoreRequest $request, $id)
    {
        try {
            $pension_fund = PensionFund::withTrashed()->findOrFail($id);
            $pension_fund->restore();

            return $this->successResponse(
                new PensionFundResource($pension_fund),
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
