<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\BootType\BootTypeAllRequest;
use App\Http\Requests\Typification\BootType\BootTypeDeleteRequest;
use App\Http\Requests\Typification\BootType\BootTypeFindRequest;
use App\Http\Requests\Typification\BootType\BootTypeRestoreRequest;
use App\Http\Requests\Typification\BootType\BootTypeStoreRequest;
use App\Http\Requests\Typification\BootType\BootTypeUpdateRequest;
use App\Http\Resources\Typification\BootType\BootTypeCollection;
use App\Http\Resources\Typification\BootType\BootTypeResource;
use App\Models\BootType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="BootTypeSettings",
 *     type="object",
 *     required={"code"},
 *     description="Configuración del tipo de bota. Se guarda tal cual llega: cualquier otra clave también se almacena.",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos.", example="2041")
 * )
 *
 * @OA\Schema(
 *     schema="BootType",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=47),
 *     @OA\Property(property="item_id", type="integer", example=18),
 *     @OA\Property(property="name", type="string", example="BOTA CON ABERTURA LATERAL"),
 *     @OA\Property(property="description", type="string", example="BOTA RECTA CON ABERTURA LATERAL DE 5 CM"),
 *     @OA\Property(property="settings", nullable=true, description="`null` o `{}` en registros antiguos sin código.", allOf={@OA\Schema(ref="#/components/schemas/BootTypeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 11:55:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 11:55:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=18),
 *         @OA\Property(property="name", type="string", example="Tipos de Bota"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de bota."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BootTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="boot_type", ref="#/components/schemas/BootType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BootTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="boot_types", type="array", @OA\Items(ref="#/components/schemas/BootType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BootTypeRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de bota.", example="BOTA CON ABERTURA LATERAL"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="BOTA RECTA CON ABERTURA LATERAL DE 5 CM"),
 *     @OA\Property(property="settings", ref="#/components/schemas/BootTypeSettings")
 * )
 */
class BootTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/boot_types/all",
     *     tags={"Typification › Boot Types"},
     *     summary="Listar los tipos de bota",
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
     *         description="Lista de tipos de bota cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BootTypeListResponse")
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
    public function all(BootTypeAllRequest $request)
    {
        try {
            $boot_types = BootType::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $boot_types = $boot_types->paginate($request->integer('per_page', $boot_types->count()));

            return $this->successResponse(
                new BootTypeCollection($boot_types),
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
     *     path="/typification/boot_types/find/{id}",
     *     tags={"Typification › Boot Types"},
     *     summary="Obtener un tipo de bota",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de bota cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BootTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(BootTypeFindRequest $request, $id)
    {
        try {
            $boot_type = BootType::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new BootTypeResource($boot_type),
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
     *     path="/typification/boot_types/store",
     *     tags={"Typification › Boot Types"},
     *     summary="Crear un tipo de bota",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BootTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de bota creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BootTypeResponse")
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
    public function store(BootTypeStoreRequest $request)
    {
        try {
            $boot_type = new BootType();
            $boot_type->name = $request->input('name');
            $boot_type->description = $request->input('description');
            $boot_type->settings = $request->input('settings');

            $boot_type->save();

            return $this->successResponse(
                new BootTypeResource($boot_type),
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
     *     path="/typification/boot_types/update/{id}",
     *     tags={"Typification › Boot Types"},
     *     summary="Editar un tipo de bota",
     *     description="Reemplaza nombre, descripción y `settings` completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/BootTypeRequest",
     *             example={"name": "BOTA PITILLO", "description": "BOTA ANGOSTA AJUSTADA AL TOBILLO", "settings": {"code": "2042"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de bota editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BootTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de bota no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de bota", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(BootTypeUpdateRequest $request, $id)
    {
        try {
            $boot_type = BootType::findOrFail($id);
            $boot_type->name = $request->input('name');
            $boot_type->description = $request->input('description');
            $boot_type->settings = $request->input('settings');

            $boot_type->save();

            return $this->successResponse(
                new BootTypeResource($boot_type),
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
     *     path="/typification/boot_types/delete/{id}",
     *     tags={"Typification › Boot Types"},
     *     summary="Desactivar un tipo de bota",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de bota desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/BootTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(BootTypeDeleteRequest $request, $id)
    {
        try {
            $boot_type = BootType::findOrFail($id);
            $boot_type->delete();

            return $this->successResponse(
                new BootTypeResource($boot_type),
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
     *     path="/typification/boot_types/restore/{id}",
     *     tags={"Typification › Boot Types"},
     *     summary="Restaurar un tipo de bota desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de bota restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BootTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(BootTypeRestoreRequest $request, $id)
    {
        try {
            $boot_type = BootType::withTrashed()->findOrFail($id);
            $boot_type->restore();

            return $this->successResponse(
                new BootTypeResource($boot_type),
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
