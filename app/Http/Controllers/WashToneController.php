<?php

namespace App\Http\Controllers;

use App\Http\Requests\WashTone\WashToneAllRequest;
use App\Http\Requests\WashTone\WashToneDeleteRequest;
use App\Http\Requests\WashTone\WashToneFindRequest;
use App\Http\Requests\WashTone\WashToneRestoreRequest;
use App\Http\Requests\WashTone\WashToneStoreRequest;
use App\Http\Requests\WashTone\WashToneUpdateRequest;
use App\Http\Resources\WashTone\WashToneCollection;
use App\Http\Resources\WashTone\WashToneResource;
use App\Models\WashTone;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Wash Tones",
 *     description="Endpoints para gestionar tonos de lavado"
 * )
 *
 * @OA\Schema(
 *     schema="WashToneSettings",
 *     type="object",
 *     required={"code"},
 *     description="Configuración del tono de lavado. Se guarda tal cual llega (reemplaza la anterior).",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos.", example="71020")
 * )
 *
 * @OA\Schema(
 *     schema="WashTone",
 *     type="object",
 *     description="Tono de lavado del jean (CLARO, MEDIO, OSCURO DIRTY…).",
 *     @OA\Property(property="id", type="integer", example=502),
 *     @OA\Property(property="item_id", type="integer", example=23),
 *     @OA\Property(property="name", type="string", example="AZUL ÍNDIGO"),
 *     @OA\Property(property="description", type="string", example="TONO AZUL ÍNDIGO SIN DESGASTE"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/WashToneSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 10:25:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 10:25:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=23),
 *         @OA\Property(property="name", type="string", example="Tonos de Lavado"),
 *         @OA\Property(property="description", type="string", example="Listado de tonos de lavado."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="WashToneResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="wash_tone", ref="#/components/schemas/WashTone")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="WashToneListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="wash_tones", type="array", @OA\Items(ref="#/components/schemas/WashTone")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="WashToneRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tonos de lavado.", example="AZUL ÍNDIGO"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="TONO AZUL ÍNDIGO SIN DESGASTE"),
 *     @OA\Property(property="settings", ref="#/components/schemas/WashToneSettings")
 * )
 */
class WashToneController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/wash_tones/all",
     *     tags={"Wash Tones"},
     *     summary="Listar los tonos de lavado",
     *     description="También la pueden consultar quienes configuran procesos, subprocesos u operaciones.",
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
     *         description="Lista de tonos de lavado cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WashToneListResponse")
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
    public function all(WashToneAllRequest $request)
    {
        try {
            $wash_tones = WashTone::with(['item'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $wash_tones = $wash_tones->paginate($request->integer('per_page', $wash_tones->count()));

            return $this->successResponse(
                new WashToneCollection($wash_tones),
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
     *     path="/wash_tones/find/{id}",
     *     tags={"Wash Tones"},
     *     summary="Obtener un tono de lavado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tono de lavado cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WashToneResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(WashToneFindRequest $request, $id)
    {
        try {
            $wash_tone = WashTone::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new WashToneResource($wash_tone),
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
     *     path="/wash_tones/store",
     *     tags={"Wash Tones"},
     *     summary="Crear un tono de lavado",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/WashToneRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tono de lavado creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WashToneResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"settings.code": {"Debe ser un dato númerico."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(WashToneStoreRequest $request)
    {
        try {
            $wash_tone = new WashTone();
            $wash_tone->name = $request->input('name');
            $wash_tone->description = $request->input('description');
            $wash_tone->settings = $request->input('settings');

            $wash_tone->save();

            return $this->successResponse(
                new WashToneResource($wash_tone),
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
     *     path="/wash_tones/update/{id}",
     *     tags={"Wash Tones"},
     *     summary="Editar un tono de lavado",
     *     description="Reemplaza nombre, descripción y `settings` completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/WashToneRequest",
     *             example={"name": "AZUL ÍNDIGO", "description": "TONO AZUL ÍNDIGO CON DESGASTE SUAVE", "settings": {"code": "71020"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tono de lavado editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WashToneResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tono de lavado no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(WashToneUpdateRequest $request, $id)
    {
        try {
            $wash_tone = WashTone::findOrFail($id);
            $wash_tone->name = $request->input('name');
            $wash_tone->description = $request->input('description');
            $wash_tone->settings = $request->input('settings');

            $wash_tone->save();

            return $this->successResponse(
                new WashToneResource($wash_tone),
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
     * @OA\Delete(
     *     path="/wash_tones/delete/{id}",
     *     tags={"Wash Tones"},
     *     summary="Desactivar un tono de lavado",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tono de lavado desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/WashToneResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(WashToneDeleteRequest $request, $id)
    {
        try {
            $wash_tone = WashTone::findOrFail($id);
            $wash_tone->delete();

            return $this->successResponse(
                new WashToneResource($wash_tone),
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
     *     path="/wash_tones/restore/{id}",
     *     tags={"Wash Tones"},
     *     summary="Restaurar un tono de lavado desactivado",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tono de lavado restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/WashToneResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(WashToneRestoreRequest $request, $id)
    {
        try {
            $wash_tone = WashTone::withTrashed()->findOrFail($id);
            $wash_tone->restore();

            return $this->successResponse(
                new WashToneResource($wash_tone),
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
