<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\BackType\BackTypeAllRequest;
use App\Http\Requests\Typification\BackType\BackTypeDeleteRequest;
use App\Http\Requests\Typification\BackType\BackTypeFindRequest;
use App\Http\Requests\Typification\BackType\BackTypeRestoreRequest;
use App\Http\Requests\Typification\BackType\BackTypeStoreRequest;
use App\Http\Requests\Typification\BackType\BackTypeUpdateRequest;
use App\Http\Resources\Typification\BackType\BackTypeCollection;
use App\Http\Resources\Typification\BackType\BackTypeResource;
use App\Models\BackType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="BackTypeSettings",
 *     type="object",
 *     required={"code"},
 *     description="Configuración del tipo de trasero. Se guarda tal cual llega: cualquier otra clave también se almacena.",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos.", example="1015")
 * )
 *
 * @OA\Schema(
 *     schema="BackType",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=31),
 *     @OA\Property(property="item_id", type="integer", example=20),
 *     @OA\Property(property="name", type="string", example="BOLSILLO DE PARCHE"),
 *     @OA\Property(property="description", type="string", example="TRASERO CON BOLSILLO DE PARCHE Y PESPUNTE DOBLE"),
 *     @OA\Property(property="settings", nullable=true, description="`null` o `{}` en registros antiguos sin código.", allOf={@OA\Schema(ref="#/components/schemas/BackTypeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 11:05:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 11:05:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=20),
 *         @OA\Property(property="name", type="string", example="Tipos de Trasero"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de trasero."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BackTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="back_type", ref="#/components/schemas/BackType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BackTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="back_types", type="array", @OA\Items(ref="#/components/schemas/BackType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BackTypeRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de trasero.", example="BOLSILLO DE PARCHE"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="TRASERO CON BOLSILLO DE PARCHE Y PESPUNTE DOBLE"),
 *     @OA\Property(property="settings", ref="#/components/schemas/BackTypeSettings")
 * )
 */
class BackTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/back_types/all",
     *     tags={"Typification › Back Types"},
     *     summary="Listar los tipos de trasero",
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
     *         description="Lista de tipos de trasero cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BackTypeListResponse")
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
    public function all(BackTypeAllRequest $request)
    {
        try {
            $back_types = BackType::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $back_types = $back_types->paginate($request->integer('per_page', $back_types->count()));

            return $this->successResponse(
                new BackTypeCollection($back_types),
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
     *     path="/typification/back_types/find/{id}",
     *     tags={"Typification › Back Types"},
     *     summary="Obtener un tipo de trasero",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de trasero cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BackTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(BackTypeFindRequest $request, $id)
    {
        try {
            $back_type = BackType::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new BackTypeResource($back_type),
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
     *     path="/typification/back_types/store",
     *     tags={"Typification › Back Types"},
     *     summary="Crear un tipo de trasero",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BackTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de trasero creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BackTypeResponse")
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
    public function store(BackTypeStoreRequest $request)
    {
        try {
            $back_type = new BackType();
            $back_type->name = $request->input('name');
            $back_type->description = $request->input('description');
            $back_type->settings = $request->input('settings');

            $back_type->save();

            return $this->successResponse(
                new BackTypeResource($back_type),
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
     *     path="/typification/back_types/update/{id}",
     *     tags={"Typification › Back Types"},
     *     summary="Editar un tipo de trasero",
     *     description="Reemplaza nombre, descripción y `settings` completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/BackTypeRequest",
     *             example={"name": "BOLSILLO CON TAPA", "description": "TRASERO CON BOLSILLO DE TAPA Y BOTÓN", "settings": {"code": "1016"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de trasero editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BackTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de trasero no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de trasero", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(BackTypeUpdateRequest $request, $id)
    {
        try {
            $back_type = BackType::findOrFail($id);
            $back_type->name = $request->input('name');
            $back_type->description = $request->input('description');
            $back_type->settings = $request->input('settings');

            $back_type->save();

            return $this->successResponse(
                new BackTypeResource($back_type),
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
     *     path="/typification/back_types/delete/{id}",
     *     tags={"Typification › Back Types"},
     *     summary="Desactivar un tipo de trasero",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de trasero desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/BackTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(BackTypeDeleteRequest $request, $id)
    {
        try {
            $back_type = BackType::findOrFail($id);
            $back_type->delete();

            return $this->successResponse(
                new BackTypeResource($back_type),
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
     *     path="/typification/back_types/restore/{id}",
     *     tags={"Typification › Back Types"},
     *     summary="Restaurar un tipo de trasero desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de trasero restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BackTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(BackTypeRestoreRequest $request, $id)
    {
        try {
            $back_type = BackType::withTrashed()->findOrFail($id);
            $back_type->restore();

            return $this->successResponse(
                new BackTypeResource($back_type),
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
