<?php

namespace App\Http\Controllers;

use App\Http\Requests\Size\SizeAllRequest;
use App\Http\Requests\Size\SizeDeleteRequest;
use App\Http\Requests\Size\SizeFindRequest;
use App\Http\Requests\Size\SizeRestoreRequest;
use App\Http\Requests\Size\SizeStoreRequest;
use App\Http\Requests\Size\SizeUpdateRequest;
use App\Http\Resources\Size\SizeCollection;
use App\Http\Resources\Size\SizeResource;
use App\Models\Size;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Sizes",
 *     description="Endpoints para gestionar tallas"
 * )
 *
 * @OA\Schema(
 *     schema="SizeSettings",
 *     type="object",
 *     required={"finished_product"},
 *     description="Configuración de la talla. Se guarda tal cual llega (reemplaza la anterior).",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos. Obligatorio al crear.", example="24"),
 *     @OA\Property(property="finished_product", type="boolean", description="Es talla de producto terminado.", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="Size",
 *     type="object",
 *     description="Talla. Las de producto terminado (`settings.finished_product = true`) son las que se usan en las curvas de las órdenes de producción.",
 *     @OA\Property(property="id", type="integer", example=467),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=16),
 *     @OA\Property(property="name", type="string", example="24"),
 *     @OA\Property(property="description", type="string", example="T24"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/SizeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 08:30:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 08:30:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=16),
 *         @OA\Property(property="name", type="string", example="Tallas"),
 *         @OA\Property(property="description", type="string", example="Listado de tallas."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SizeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="size", ref="#/components/schemas/Size")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SizeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="sizes", type="array", @OA\Items(ref="#/components/schemas/Size")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SizeRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=2, maxLength=50, description="Única entre las tallas. Si es de producto terminado, debe ser un número par de 2 dígitos o XS, S, M, L, XL, XXL (regla guardada en el ítem 16, `settings.finished_product.name`).", example="24"),
 *     @OA\Property(property="description", type="string", maxLength=50, description="Si es de producto terminado, debe ser T seguida de al menos 2 dígitos (regla en `settings.finished_product.description` del ítem 16).", example="T24"),
 *     @OA\Property(property="settings", ref="#/components/schemas/SizeSettings")
 * )
 */
class SizeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/sizes/all",
     *     tags={"Sizes"},
     *     summary="Listar las tallas",
     *     description="También la pueden consultar quienes configuran procesos, subprocesos u operaciones (`workflow.processes...settings`) y quienes ven marcas (`trademarks.find`).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="is_finished_product",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: solo las tallas de producto terminado. `false` o sin enviar: todas.",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
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
     *         description="Lista de tallas cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SizeListResponse")
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
    public function all(SizeAllRequest $request)
    {
        try {
            $sizes = Size::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->boolean('is_finished_product'), function ($query) {
                    return $query->where('settings->finished_product', true);
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $sizes = $sizes->paginate($request->integer('per_page', $sizes->count()));

            return $this->successResponse(
                new SizeCollection($sizes),
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
     *     path="/sizes/find/{id}",
     *     tags={"Sizes"},
     *     summary="Obtener una talla",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Talla cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SizeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(SizeFindRequest $request, $id)
    {
        try {
            $size = Size::findOrFail($id);

            return $this->successResponse(
                new SizeResource($size),
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
     *     path="/sizes/store",
     *     tags={"Sizes"},
     *     summary="Crear una talla",
     *     description="Al crear, `settings.code` es obligatorio (2 a 5 dígitos).",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SizeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Talla creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SizeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código", "settings.finished_product": "Es producto terminado"},
     *                 "errors": {"name": {"Debe ser una talla válida o un número par de 2 dígitos."}, "description": {"Debe iniciar con ""T"" seguido de al menos 2 números."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(SizeStoreRequest $request)
    {
        try {
            $size = new Size();
            $size->name = $request->input('name');
            $size->description = $request->input('description');
            $size->settings = $request->input('settings');
            $size->save();

            return $this->successResponse(
                new SizeResource($size),
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
     *     path="/sizes/update/{id}",
     *     tags={"Sizes"},
     *     summary="Editar una talla",
     *     description="Reemplaza nombre, descripción y `settings` completo. Al editar `settings.code` no se valida: si no se envía, se pierde (ver pendientes).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/SizeRequest",
     *             example={"name": "26", "description": "T26", "settings": {"code": "26", "finished_product": true}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Talla editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SizeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o talla no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador de la talla", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.finished_product": "Es producto terminado"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(SizeUpdateRequest $request, $id)
    {
        try {
            $size = Size::findOrFail($id);
            $size->name = $request->input('name');
            $size->description = $request->input('description');
            $size->settings = $request->input('settings');
            $size->save();

            return $this->successResponse(
                new SizeResource($size),
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
     *     path="/sizes/delete/{id}",
     *     tags={"Sizes"},
     *     summary="Desactivar una talla",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Talla desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/SizeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(SizeDeleteRequest $request, $id)
    {
        try {
            $size = Size::findOrFail($id);
            $size->delete();

            return $this->successResponse(
                new SizeResource($size),
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
     *     path="/sizes/restore/{id}",
     *     tags={"Sizes"},
     *     summary="Restaurar una talla desactivada",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Talla restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SizeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(SizeRestoreRequest $request, $id)
    {
        try {
            $size = Size::withTrashed()->findOrFail($id);
            $size->restore();

            return $this->successResponse(
                new SizeResource($size),
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
