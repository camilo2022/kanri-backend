<?php

namespace App\Http\Controllers;

use App\Http\Requests\Piece\PieceAllRequest;
use App\Http\Requests\Piece\PieceDeleteRequest;
use App\Http\Requests\Piece\PieceFindRequest;
use App\Http\Requests\Piece\PieceRestoreRequest;
use App\Http\Requests\Piece\PieceStoreRequest;
use App\Http\Requests\Piece\PieceUpdateRequest;
use App\Http\Resources\Piece\PieceResource;
use App\Http\Resources\Piece\PieceCollection;
use App\Models\Piece;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Pieces",
 *     description="Endpoints para gestionar piezas"
 * )
 *
 * @OA\Schema(
 *     schema="Piece",
 *     type="object",
 *     description="Pieza de la prenda (bolsillo, pasador, aletilla…) que se usa en las órdenes de producción.",
 *     @OA\Property(property="id", type="integer", example=412),
 *     @OA\Property(property="item_id", type="integer", example=31),
 *     @OA\Property(property="name", type="string", example="PRETINA"),
 *     @OA\Property(property="description", type="string", example="PRETINA DEL JEAN"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 10:40:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 10:40:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=31),
 *         @OA\Property(property="name", type="string", example="Piezas"),
 *         @OA\Property(property="description", type="string", example="Listado de piezas."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PieceResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="piece", ref="#/components/schemas/Piece")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PieceListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="pieces", type="array", @OA\Items(ref="#/components/schemas/Piece")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PieceRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre las piezas.", example="PRETINA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="PRETINA DEL JEAN")
 * )
 */
class PieceController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/pieces/all",
     *     tags={"Pieces"},
     *     summary="Listar las piezas",
     *     description="También la pueden consultar quienes tienen permiso de configurar procesos, subprocesos u operaciones (`workflow.processes...settings`). En esta lista no viene `settings`.",
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
     *         description="Lista de piezas cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PieceListResponse")
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
    public function all(PieceAllRequest $request)
    {
        try {
            $pieces = Piece::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $pieces = $pieces->paginate($request->integer('per_page', $pieces->count()));

            return $this->successResponse(
                new PieceCollection($pieces),
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
     *     path="/pieces/find/{id}",
     *     tags={"Pieces"},
     *     summary="Obtener una pieza",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Pieza cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PieceResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(PieceFindRequest $request, $id)
    {
        try {
            $piece = Piece::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new PieceResource($piece),
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
     *     path="/pieces/store",
     *     tags={"Pieces"},
     *     summary="Crear una pieza",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PieceRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pieza creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PieceResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(PieceStoreRequest $request)
    {
        try {
            $piece = new Piece();
            $piece->name = $request->input('name');
            $piece->description = $request->input('description');
            $piece->save();

            return $this->successResponse(
                new PieceResource($piece),
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
     *     path="/pieces/update/{id}",
     *     tags={"Pieces"},
     *     summary="Editar una pieza",
     *     description="Reemplaza nombre y descripción.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/PieceRequest",
     *             example={"name": "PARCHE", "description": "PARCHE TRASERO DE MARCA"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pieza editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PieceResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o pieza no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador de la linea", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(PieceUpdateRequest $request, $id)
    {
        try {
            $piece = Piece::findOrFail($id);
            $piece->name = $request->input('name');
            $piece->description = $request->input('description');
            $piece->save();

            return $this->successResponse(
                new PieceResource($piece),
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
     *     path="/pieces/delete/{id}",
     *     tags={"Pieces"},
     *     summary="Desactivar una pieza",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Pieza desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/PieceResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(PieceDeleteRequest $request, $id)
    {
        try {
            $piece = Piece::findOrFail($id);
            $piece->delete();

            return $this->successResponse(
                new PieceResource($piece),
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
     *     path="/pieces/restore/{id}",
     *     tags={"Pieces"},
     *     summary="Restaurar una pieza desactivada",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Pieza restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PieceResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(PieceRestoreRequest $request, $id)
    {
        try {
            $piece = Piece::withTrashed()->findOrFail($id);
            $piece->restore();

            return $this->successResponse(
                new PieceResource($piece),
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
