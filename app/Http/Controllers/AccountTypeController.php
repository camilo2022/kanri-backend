<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\AccountType\AccountTypeAllRequest;
use App\Http\Requests\Typification\AccountType\AccountTypeDeleteRequest;
use App\Http\Requests\Typification\AccountType\AccountTypeFindRequest;
use App\Http\Requests\Typification\AccountType\AccountTypeRestoreRequest;
use App\Http\Requests\Typification\AccountType\AccountTypeStoreRequest;
use App\Http\Requests\Typification\AccountType\AccountTypeUpdateRequest;
use App\Http\Resources\Typification\AccountType\AccountTypeCollection;
use App\Http\Resources\Typification\AccountType\AccountTypeResource;
use App\Models\AccountType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="AccountType",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=3),
 *     @OA\Property(property="item_id", type="integer", example=39),
 *     @OA\Property(property="name", type="string", example="CUENTA DE AHORROS"),
 *     @OA\Property(property="description", type="string", example="CUENTA DE AHORROS PARA PAGO DE NÓMINA"),
 *     @OA\Property(property="settings", type="object", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 09:32:10"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 09:32:10"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=39),
 *         @OA\Property(property="name", type="string", example="Tipos de Cuenta"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de cuenta."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AccountTypeResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="account_type", ref="#/components/schemas/AccountType")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="AccountTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="account_types", type="array", @OA\Items(ref="#/components/schemas/AccountType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="AccountTypeRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         minLength=4,
 *         maxLength=50,
 *         description="Nombre en MAYÚSCULAS. Único entre los tipos de cuenta.",
 *         example="CUENTA CORRIENTE"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         maxLength=255,
 *         description="Descripción en MAYÚSCULAS.",
 *         example="CUENTA CORRIENTE PARA PAGO A PROVEEDORES"
 *     )
 * )
 */
class AccountTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/account_types/all",
     *     tags={"Typification › Account Types"},
     *     summary="Listar los tipos de cuenta",
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
     *         description="Lista de tipos de cuenta cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AccountTypeListResponse")
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
    public function all(AccountTypeAllRequest $request)
    {
        try {
            $account_types = AccountType::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $account_types = $account_types->paginate($request->integer('per_page', $account_types->count()));

            return $this->successResponse(
                new AccountTypeCollection($account_types),
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
     *     path="/typification/account_types/find/{id}",
     *     tags={"Typification › Account Types"},
     *     summary="Obtener un tipo de cuenta",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de cuenta cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AccountTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(AccountTypeFindRequest $request, $id)
    {
        try {
            $account_type = AccountType::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new AccountTypeResource($account_type),
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
     *     path="/typification/account_types/store",
     *     tags={"Typification › Account Types"},
     *     summary="Crear un tipo de cuenta",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AccountTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de cuenta creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AccountTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Debe de ingresar mayúsculas."}, "description": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(AccountTypeStoreRequest $request)
    {
        try {
            $account_type = new AccountType;
            $account_type->name = $request->input('name');
            $account_type->description = $request->input('description');

            $account_type->save();

            return $this->successResponse(
                new AccountTypeResource($account_type),
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
     *     path="/typification/account_types/update/{id}",
     *     tags={"Typification › Account Types"},
     *     summary="Editar un tipo de cuenta",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/AccountTypeRequest",
     *             example={"name": "CUENTA DE AHORROS", "description": "CUENTA DE AHORROS PARA PAGO DE NÓMINA"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de cuenta editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AccountTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de cuenta no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de cuenta", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(AccountTypeUpdateRequest $request, $id)
    {
        try {
            $account_type = AccountType::findOrFail($id);
            $account_type->name = $request->input('name');
            $account_type->description = $request->input('description');

            $account_type->save();

            return $this->successResponse(
                new AccountTypeResource($account_type),
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
     *     path="/typification/account_types/delete/{id}",
     *     tags={"Typification › Account Types"},
     *     summary="Desactivar un tipo de cuenta",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de cuenta desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/AccountTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(AccountTypeDeleteRequest $request, $id)
    {
        try {
            $account_type = AccountType::findOrFail($id);
            $account_type->delete();

            return $this->successResponse(
                new AccountTypeResource($account_type),
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
     *     path="/typification/account_types/restore/{id}",
     *     tags={"Typification › Account Types"},
     *     summary="Restaurar un tipo de cuenta desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de cuenta restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AccountTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(AccountTypeRestoreRequest $request, $id)
    {
        try {
            $account_type = AccountType::withTrashed()->findOrFail($id);
            $account_type->restore();

            return $this->successResponse(
                new AccountTypeResource($account_type),
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
