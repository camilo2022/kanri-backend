<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\BloodType\BloodTypeAllRequest;
use App\Http\Requests\Typification\BloodType\BloodTypeDeleteRequest;
use App\Http\Requests\Typification\BloodType\BloodTypeFindRequest;
use App\Http\Requests\Typification\BloodType\BloodTypeRestoreRequest;
use App\Http\Requests\Typification\BloodType\BloodTypeStoreRequest;
use App\Http\Requests\Typification\BloodType\BloodTypeUpdateRequest;
use App\Http\Resources\Typification\BloodType\BloodTypeCollection;
use App\Http\Resources\Typification\BloodType\BloodTypeResource;
use App\Models\BloodType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="BloodType",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=318),
 *     @OA\Property(property="item_id", type="integer", example=6),
 *     @OA\Property(property="name", type="string", example="AB+"),
 *     @OA\Property(property="description", type="string", example="AB POSITIVO"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 11:40:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 11:40:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=6),
 *         @OA\Property(property="name", type="string", example="Tipos de sangre"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de sangre."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BloodTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="blood_type", ref="#/components/schemas/BloodType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BloodTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="blood_types", type="array", @OA\Items(ref="#/components/schemas/BloodType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BloodTypeRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         pattern="^(A|B|AB|O)[+-]$",
 *         description="Grupo sanguíneo y factor Rh: A, B, AB u O seguido de + o -. Único entre los tipos de sangre.",
 *         example="AB+"
 *     ),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="AB POSITIVO")
 * )
 */
class BloodTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/blood_types/all",
     *     tags={"Typification › Blood Types"},
     *     summary="Listar los tipos de sangre",
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
     *         description="Lista de tipos de sangre cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BloodTypeListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"column": {"Valor inválido. Permitidos: id, name, description, created_at, updated_at, deleted_at."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(BloodTypeAllRequest $request)
    {
        try {
            $blood_types = BloodType::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $blood_types = $blood_types->paginate($request->integer('per_page', $blood_types->count()));

            return $this->successResponse(
                new BloodTypeCollection($blood_types),
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
     *     path="/typification/blood_types/find/{id}",
     *     tags={"Typification › Blood Types"},
     *     summary="Obtener un tipo de sangre",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de sangre cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BloodTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(BloodTypeFindRequest $request, $id)
    {
        try {
            $blood_type = BloodType::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new BloodTypeResource($blood_type),
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
     *     path="/typification/blood_types/store",
     *     tags={"Typification › Blood Types"},
     *     summary="Crear un tipo de sangre",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BloodTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de sangre creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BloodTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Formato Inválido. Es un tipo de sangre no válido."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(BloodTypeStoreRequest $request)
    {
        try {
            $blood_type = new BloodType();
            $blood_type->name = $request->input('name');
            $blood_type->description = $request->input('description');
            $blood_type->save();

            return $this->successResponse(
                new BloodTypeResource($blood_type),
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
     *     path="/typification/blood_types/update/{id}",
     *     tags={"Typification › Blood Types"},
     *     summary="Editar un tipo de sangre",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/BloodTypeRequest",
     *             example={"name": "AB-", "description": "AB NEGATIVO"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de sangre editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BloodTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de sangre no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de sangre", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(BloodTypeUpdateRequest $request, $id)
    {
        try {
            $blood_type = BloodType::findOrFail($id);
            $blood_type->name = $request->input('name');
            $blood_type->description = $request->input('description');
            $blood_type->save();

            return $this->successResponse(
                new BloodTypeResource($blood_type),
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
     *     path="/typification/blood_types/delete/{id}",
     *     tags={"Typification › Blood Types"},
     *     summary="Desactivar un tipo de sangre",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de sangre desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/BloodTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(BloodTypeDeleteRequest $request, $id)
    {
        try {
            $blood_type = BloodType::findOrFail($id);
            $blood_type->delete();

            return $this->successResponse(
                new BloodTypeResource($blood_type),
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
     *     path="/typification/blood_types/restore/{id}",
     *     tags={"Typification › Blood Types"},
     *     summary="Restaurar un tipo de sangre desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de sangre restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BloodTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(BloodTypeRestoreRequest $request, $id)
    {
        try {
            $blood_type = BloodType::withTrashed()->findOrFail($id);
            $blood_type->restore();

            return $this->successResponse(
                new BloodTypeResource($blood_type),
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
