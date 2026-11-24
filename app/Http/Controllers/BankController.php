<?php

namespace App\Http\Controllers;

use App\Http\Requests\Bank\BankAllRequest;
use App\Http\Requests\Bank\BankDeleteRequest;
use App\Http\Requests\Bank\BankFindRequest;
use App\Http\Requests\Bank\BankRestoreRequest;
use App\Http\Requests\Bank\BankSettingRequest;
use App\Http\Requests\Bank\BankStoreRequest;
use App\Http\Requests\Bank\BankUpdateRequest;
use App\Http\Resources\Bank\BankCollection;
use App\Http\Resources\Bank\BankResource;
use App\Models\Bank;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Bank",
 *     description="Endpoints para gestionar bancos"
 * )
 *
 * @OA\Schema(
 *     schema="Bank",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=452),
 *     @OA\Property(property="item_id", type="integer", example=38),
 *     @OA\Property(property="name", type="string", example="BANCO AGRARIO DE COLOMBIA"),
 *     @OA\Property(property="description", type="string", example="BANCO AGRARIO DE COLOMBIA S.A."),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         description="`validations`: regla del número de cuenta por cada tipo de cuenta que el banco maneja (los tipos asociados al banco). Un tipo sin regla no lo maneja; un banco sin reglas no recibe cuentas.",
 *         example={"validations": {{"account_type_id": 452, "regex": "/^\d{11}$/", "message": "La cuenta de ahorros de Bancolombia tiene 11 dígitos."}}}
 *     ),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 11:20:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 11:20:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=38),
 *         @OA\Property(property="name", type="string", example="Bancos"),
 *         @OA\Property(property="description", type="string", example="Listado de bancos"),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BankResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="bank", ref="#/components/schemas/Bank")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BankListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="banks", type="array", @OA\Items(ref="#/components/schemas/Bank")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BankRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los bancos.", example="BANCO AGRARIO DE COLOMBIA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="BANCO AGRARIO DE COLOMBIA S.A.")
 * )
 */
class BankController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/banks/all",
     *     tags={"Bank"},
     *     summary="Listar los bancos",
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
     *         description="Lista de bancos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BankListResponse")
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
    public function all(BankAllRequest $request)
    {
        try {
            $bank = Bank::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $bank = $bank->paginate($request->integer('per_page', $bank->count()));

            return $this->successResponse(
                new BankCollection($bank),
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
     *     path="/banks/find/{id}",
     *     tags={"Bank"},
     *     summary="Obtener un banco",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Banco cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BankResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(BankFindRequest $request, $id)
    {
        try {
            $bank = Bank::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new BankResource($bank),
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
     *     path="/banks/store",
     *     tags={"Bank"},
     *     summary="Crear un banco",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BankRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Banco creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BankResponse")
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
    public function store(BankStoreRequest $request)
    {
        try {
            $bank = new Bank;
            $bank->name = $request->input('name');
            $bank->description = $request->input('description');

            $bank->save();

            return $this->successResponse(
                new BankResource($bank),
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
     *     path="/banks/update/{id}",
     *     tags={"Bank"},
     *     summary="Editar un banco",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/BankRequest",
     *             example={"name": "BANCO UNIÓN", "description": "BANCO UNIÓN S.A."}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Banco editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BankResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o banco no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del banco", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"description": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(BankUpdateRequest $request, $id)
    {
        try {
            $bank = Bank::findOrFail($id);
            $bank->name = $request->input('name');
            $bank->description = $request->input('description');

            $bank->save();

            return $this->successResponse(
                new BankResource($bank),
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
     *     path="/banks/delete/{id}",
     *     tags={"Bank"},
     *     summary="Desactivar un banco",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Banco desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/BankResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(BankDeleteRequest $request, $id)
    {
        try {
            $bank = Bank::findOrFail($id);
            $bank->delete();

            return $this->successResponse(
                new BankResource($bank),
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
     *     path="/banks/restore/{id}",
     *     tags={"Bank"},
     *     summary="Restaurar un banco desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Banco restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BankResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(BankRestoreRequest $request, $id)
    {
        try {
            $bank = Bank::withTrashed()->findOrFail($id);
            $bank->restore();

            return $this->successResponse(
                new BankResource($bank),
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
     * @OA\Put(
     *     path="/banks/setting/{id}",
     *     tags={"Bank"},
     *     summary="Configurar las reglas de las cuentas del banco",
     *     description="Reemplaza `settings.validations` del banco: una fila por cada tipo de cuenta que el banco maneja, con el regex y el mensaje del número de cuenta (los tres obligatorios). Un tipo que no esté en la lista no lo maneja el banco. Las cuentas de proveedores se validan con la regla de su banco y su tipo; una lista vacía deja al banco sin recibir cuentas.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"settings"},
     *             example={"settings": {"validations": {
     *                 {"account_type_id": 452, "regex": "/^\d{11}$/", "message": "La cuenta de ahorros de Bancolombia tiene 11 dígitos."}
     *             }}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reglas guardadas",
     *         @OA\JsonContent(ref="#/components/schemas/BankResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Falta el tipo, el regex o el mensaje de una fila, el regex no es válido o el tipo de cuenta no existe o se repite.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "errors": {"settings.validations.0.regex": {"Debe ser una expresión regular válida, con delimitadores (por ejemplo /^\d{11}$/)."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function setting(BankSettingRequest $request, $id)
    {
        try {
            $bank = Bank::findOrFail($id);
            $bank->settings = $request->input('settings');
            $bank->save();

            return $this->successResponse(
                new BankResource($bank),
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
