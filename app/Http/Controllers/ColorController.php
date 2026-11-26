<?php

namespace App\Http\Controllers;

use App\Http\Requests\Color\ColorAllRequest;
use App\Http\Requests\Color\ColorDeleteRequest;
use App\Http\Requests\Color\ColorFindRequest;
use App\Http\Requests\Color\ColorRestoreRequest;
use App\Http\Requests\Color\ColorStoreRequest;
use App\Http\Requests\Color\ColorUpdateRequest;
use App\Http\Resources\Color\ColorCollection;
use App\Http\Resources\Color\ColorResource;
use App\Models\Color;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Color",
 *     description="Endpoints para gestionar colores"
 * )
 *
 * @OA\Schema(
 *     schema="ColorSettings",
 *     type="object",
 *     required={"code", "finished_product"},
 *     description="Configuración del color. Se guarda tal cual llega.",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos. Único entre los colores.", example="10101"),
 *     @OA\Property(property="finished_product", type="boolean", description="Si el color aplica a producto terminado.", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="Color",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=377),
 *     @OA\Property(property="item_id", type="integer", example=22),
 *     @OA\Property(property="name", type="string", example="AZUL ÍNDIGO"),
 *     @OA\Property(property="description", type="string", example="AZUL ÍNDIGO OSCURO PARA DENIM"),
 *     @OA\Property(property="settings", nullable=true, description="`{}` en colores antiguos sin código.", allOf={@OA\Schema(ref="#/components/schemas/ColorSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 13:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 13:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=22),
 *         @OA\Property(property="name", type="string", example="Colores"),
 *         @OA\Property(property="description", type="string", example="Listado de colores."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ColorResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="color", ref="#/components/schemas/Color")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ColorListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="colors", type="array", @OA\Items(ref="#/components/schemas/Color")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ColorRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los colores.", example="AZUL ÍNDIGO"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="AZUL ÍNDIGO OSCURO PARA DENIM"),
 *     @OA\Property(property="settings", ref="#/components/schemas/ColorSettings")
 * )
 */
class ColorController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/colors/all",
     *     tags={"Color"},
     *     summary="Listar los colores",
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
     *     @OA\Parameter(
     *         name="is_finished_product",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: solo los colores de producto terminado. `false`/`0`: solo los de insumo. Sin enviar: todos.",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de colores cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ColorListResponse")
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
    public function all(ColorAllRequest $request)
    {
        try {
            $colors = Color::with(['item'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->has('is_finished_product'), function ($query) use ($request) {
                    return $query->where('settings->finished_product', $request->boolean('is_finished_product'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $colors = $colors->paginate($request->integer('per_page', $colors->count()));

            return $this->successResponse(
                new ColorCollection($colors),
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
     *     path="/colors/find/{id}",
     *     tags={"Color"},
     *     summary="Obtener un color",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Color cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ColorResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(ColorFindRequest $request, $id)
    {
        try {
            $color = Color::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                new ColorResource($color),
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
     *     path="/colors/store",
     *     tags={"Color"},
     *     summary="Crear un color",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ColorRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Color creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ColorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o código repetido.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código", "settings.finished_product": "Es producto terminado"},
     *                 "errors": {"settings.code": {"Debe tener entre 2 y 5 dígitos."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(ColorStoreRequest $request)
    {
        try {
            $color = new Color();
            $color->name = $request->input('name');
            $color->description = $request->input('description');
            $color->settings = $request->input('settings');

            $color->save();

            return $this->successResponse(
                new ColorResource($color),
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
     *     path="/colors/update/{id}",
     *     tags={"Color"},
     *     summary="Editar un color",
     *     description="Reemplaza nombre, descripción y `settings` completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ColorRequest",
     *             example={"name": "TERRACOTA", "description": "TONO TIERRA ROJIZO", "settings": {"code": "10102", "finished_product": true}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Color editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ColorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos, color no registrado o código repetido.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del color", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código", "settings.finished_product": "Es producto terminado"},
     *                 "errors": {"settings.finished_product": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(ColorUpdateRequest $request, $id)
    {
        try {
            $color = Color::findOrFail($id);
            $color->name = $request->input('name');
            $color->description = $request->input('description');
            $color->settings = $request->input('settings');

            $color->save();

            return $this->successResponse(
                new ColorResource($color),
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
     *     path="/colors/delete/{id}",
     *     tags={"Color"},
     *     summary="Desactivar un color",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Color desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/ColorResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(ColorDeleteRequest $request, $id)
    {
        try {
            $color = Color::findOrFail($id);
            $color->delete();

            return $this->successResponse(
                new ColorResource($color),
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
     *     path="/colors/restore/{id}",
     *     tags={"Color"},
     *     summary="Restaurar un color desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Color restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ColorResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(ColorRestoreRequest $request, $id)
    {
        try {
            $color = Color::withTrashed()->findOrFail($id);
            $color->restore();

            return $this->successResponse(
                new ColorResource($color),
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
