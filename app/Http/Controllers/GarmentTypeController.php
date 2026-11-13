<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\GarmentType\GarmentTypeAllRequest;
use App\Http\Requests\Typification\GarmentType\GarmentTypeDeleteRequest;
use App\Http\Requests\Typification\GarmentType\GarmentTypeFindRequest;
use App\Http\Requests\Typification\GarmentType\GarmentTypeRestoreRequest;
use App\Http\Requests\Typification\GarmentType\GarmentTypeStoreRequest;
use App\Http\Requests\Typification\GarmentType\GarmentTypeUpdateRequest;
use App\Http\Resources\Typification\GarmentType\GarmentTypeCollection;
use App\Http\Resources\Typification\GarmentType\GarmentTypeResource;
use App\Models\GarmentType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="GarmentTypeSettings",
 *     type="object",
 *     required={"code"},
 *     description="Configuración del tipo de prenda. Se guarda tal cual llega: cualquier otra clave también se almacena.",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos. Único entre los tipos de prenda.", example="1010")
 * )
 *
 * @OA\Schema(
 *     schema="GarmentType",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=55),
 *     @OA\Property(property="item_id", type="integer", example=17),
 *     @OA\Property(property="name", type="string", example="PREMIUM"),
 *     @OA\Property(property="description", type="string", example="LINEA PREMIUM DE ALTA GAMA"),
 *     @OA\Property(property="settings", nullable=true, description="`null` o `{}` en registros antiguos sin código.", allOf={@OA\Schema(ref="#/components/schemas/GarmentTypeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 15:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 15:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=17),
 *         @OA\Property(property="name", type="string", example="Tipos de Prenda"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de prenda."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="GarmentTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="garment_type", ref="#/components/schemas/GarmentType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="GarmentTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="garment_types", type="array", @OA\Items(ref="#/components/schemas/GarmentType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="GarmentTypeRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, pattern="^[A-Z ]+$", description="Solo letras A-Z sin tildes ni Ñ y espacios (sin números ni signos). Único entre los tipos de prenda.", example="PREMIUM"),
 *     @OA\Property(property="description", type="string", maxLength=255, pattern="^[A-Z ]+$", description="Solo letras A-Z sin tildes ni Ñ y espacios (sin números ni signos).", example="LINEA PREMIUM DE ALTA GAMA"),
 *     @OA\Property(property="settings", ref="#/components/schemas/GarmentTypeSettings")
 * )
 */
class GarmentTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/garment_types/all",
     *     tags={"Typification › Garment Types"},
     *     summary="Listar los tipos de prenda",
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
     *         description="Lista de tipos de prenda cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GarmentTypeListResponse")
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
    public function all(GarmentTypeAllRequest $request)
    {
        try {
            $garment_types = GarmentType::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $garment_types = $garment_types->paginate($request->integer('per_page', $garment_types->count()));

            return $this->successResponse(
                new GarmentTypeCollection($garment_types),
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
     *     path="/typification/garment_types/find/{id}",
     *     tags={"Typification › Garment Types"},
     *     summary="Obtener un tipo de prenda",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de prenda cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GarmentTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(GarmentTypeFindRequest $request, $id)
    {
        try {
            $garment_type = GarmentType::findOrFail($id);

            return $this->successResponse(
                new GarmentTypeResource($garment_type),
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
     *     path="/typification/garment_types/store",
     *     tags={"Typification › Garment Types"},
     *     summary="Crear un tipo de prenda",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/GarmentTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de prenda creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GarmentTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o código repetido.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"name": {"Formato Inválido. El campo debe estar en mayúsculas."}, "settings.code": {"El código ya se encuentra registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(GarmentTypeStoreRequest $request)
    {
        try {
            $garment_type = new GarmentType();
            $garment_type->name = $request->input('name');
            $garment_type->description = $request->input('description');
            $garment_type->settings = $request->input('settings');

            $garment_type->save();

            return $this->successResponse(
                new GarmentTypeResource($garment_type),
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
     *     path="/typification/garment_types/update/{id}",
     *     tags={"Typification › Garment Types"},
     *     summary="Editar un tipo de prenda",
     *     description="Reemplaza nombre, descripción y `settings` completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/GarmentTypeRequest",
     *             example={"name": "CASUAL", "description": "PRENDA DE USO DIARIO", "settings": {"code": "1011"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de prenda editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GarmentTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de prenda no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de prenda", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(GarmentTypeUpdateRequest $request, $id)
    {
        try {
            $garment_type = GarmentType::findOrFail($id);
            $garment_type->name = $request->input('name');
            $garment_type->description = $request->input('description');
            $garment_type->settings = $request->input('settings');

            $garment_type->save();

            return $this->successResponse(
                new GarmentTypeResource($garment_type),
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
     *     path="/typification/garment_types/delete/{id}",
     *     tags={"Typification › Garment Types"},
     *     summary="Desactivar un tipo de prenda",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de prenda desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/GarmentTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(GarmentTypeDeleteRequest $request, $id)
    {
        try {
            $garment_type = GarmentType::findOrFail($id);
            $garment_type->delete();

            return $this->successResponse(
                new GarmentTypeResource($garment_type),
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
     *     path="/typification/garment_types/restore/{id}",
     *     tags={"Typification › Garment Types"},
     *     summary="Restaurar un tipo de prenda desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de prenda restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/GarmentTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(GarmentTypeRestoreRequest $request, $id)
    {
        try {
            $garment_type = GarmentType::withTrashed()->findOrFail($id);
            $garment_type->restore();

            return $this->successResponse(
                new GarmentTypeResource($garment_type),
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
