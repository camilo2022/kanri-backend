<?php

namespace App\Http\Controllers;

use App\Http\Requests\Gender\GenderAllRequest;
use App\Http\Requests\Gender\GenderDeleteRequest;
use App\Http\Requests\Gender\GenderFindRequest;
use App\Http\Requests\Gender\GenderRestoreRequest;
use App\Http\Requests\Gender\GenderStoreRequest;
use App\Http\Requests\Gender\GenderUpdateRequest;
use App\Http\Resources\Gender\GenderCollection;
use App\Http\Resources\Gender\GenderResource;
use App\Models\Gender;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Genders",
 *     description="Endpoints para gestionar géneros"
 * )
 *
 * @OA\Schema(
 *     schema="Gender",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=512),
 *     @OA\Property(property="item_id", type="integer", example=5),
 *     @OA\Property(property="name", type="string", description="Abreviatura del género.", example="O"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Otro"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 15:10:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 15:10:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=5),
 *         @OA\Property(property="name", type="string", example="Géneros"),
 *         @OA\Property(property="description", type="string", example="Listado de géneros disponibles."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="GenderResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="gender", ref="#/components/schemas/Gender")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="GenderListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="genders", type="array", @OA\Items(ref="#/components/schemas/Gender")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="GenderRequest",
 *     type="object",
 *     required={"name", "description"},
 *     description="Nombre y descripción en MAYÚSCULAS.",
 *     @OA\Property(property="name", type="string", minLength=1, maxLength=50, description="Abreviatura. Única entre los géneros.", example="O"),
 *     @OA\Property(property="description", type="string", maxLength=255, example="OTRO")
 * )
 */
class GenderController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/genders/all",
     *     tags={"Genders"},
     *     summary="Listar los géneros",
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
     *         description="Lista de géneros cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GenderListResponse")
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
    public function all(GenderAllRequest $request)
    {
        try {
            $genders = Gender::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $genders = $genders->paginate($request->integer('per_page', $genders->count()));

            return $this->successResponse(
                new GenderCollection($genders),
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
     *     path="/genders/find/{id}",
     *     tags={"Genders"},
     *     summary="Obtener un género",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Género cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GenderResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(GenderFindRequest $request, $id)
    {
        try {
            $gender = Gender::findOrFail($id);

            return $this->successResponse(
                new GenderResource($gender),
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
     *     path="/genders/store",
     *     tags={"Genders"},
     *     summary="Crear un género",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "description"},
     *             allOf={@OA\Schema(ref="#/components/schemas/GenderRequest")},
     *             example={"name": "O", "description": "Otro"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Género creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GenderResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}, "description": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(GenderStoreRequest $request)
    {
        try {
            $gender = new Gender();
            $gender->name = $request->input('name');
            $gender->description = $request->input('description');
            $gender->save();

            return $this->successResponse(
                new GenderResource($gender),
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
     *     path="/genders/update/{id}",
     *     tags={"Genders"},
     *     summary="Editar un género",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/GenderRequest",
     *             example={"name": "X", "description": "No especificado"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Género editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GenderResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o género no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del género", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(GenderUpdateRequest $request, $id)
    {
        try {
            $gender = Gender::findOrFail($id);
            $gender->name = $request->input('name');
            $gender->description = $request->input('description');
            $gender->save();

            return $this->successResponse(
                new GenderResource($gender),
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
     *     path="/genders/delete/{id}",
     *     tags={"Genders"},
     *     summary="Desactivar un género",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Género desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/GenderResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(GenderDeleteRequest $request, $id)
    {
        try {
            $gender = Gender::findOrFail($id);
            $gender->delete();

            return $this->successResponse(
                new GenderResource($gender),
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
     *     path="/genders/restore/{id}",
     *     tags={"Genders"},
     *     summary="Restaurar un género desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Género restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GenderResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(GenderRestoreRequest $request, $id)
    {
        try {
            $gender = Gender::withTrashed()->findOrFail($id);
            $gender->restore();

            return $this->successResponse(
                new GenderResource($gender),
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
