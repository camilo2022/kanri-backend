<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trademark\TrademarkAllRequest;
use App\Http\Requests\Trademark\TrademarkDeleteRequest;
use App\Http\Requests\Trademark\TrademarkFindRequest;
use App\Http\Requests\Trademark\TrademarkRestoreRequest;
use App\Http\Requests\Trademark\TrademarkSettingRequest;
use App\Http\Requests\Trademark\TrademarkSizeAssignRequest;
use App\Http\Requests\Trademark\TrademarkSizeRemoveRequest;
use App\Http\Requests\Trademark\TrademarkStoreRequest;
use App\Http\Requests\Trademark\TrademarkUpdateRequest;
use App\Http\Resources\Trademark\TrademarkCollection;
use App\Http\Resources\Trademark\TrademarkResource;
use App\Models\Trademark;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Trademarks",
 *     description="Endpoints para gestionar marcas"
 * )
 *
 * @OA\Schema(
 *     schema="TrademarkValidation",
 *     type="object",
 *     required={"id", "regex", "message", "created_at"},
 *     description="Regla para el código de las referencias (productos) de la marca. Un código es válido si cumple al menos una regla activa.",
 *     @OA\Property(property="id", type="integer", description="Distinto en cada regla.", example=1),
 *     @OA\Property(property="regex", type="string", description="Expresión regular de PHP válida, distinta en cada regla.", example="/^LM[0-9]{6}$/"),
 *     @OA\Property(property="message", type="string", maxLength=255, description="Mensaje cuando el código no la cumple.", example="Debe iniciar con LM y tener 6 números"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T15:00:00.000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, description="Si tiene fecha, la regla está desactivada.", example=null)
 * )
 *
 * @OA\Schema(
 *     schema="TrademarkSettings",
 *     type="object",
 *     description="Configuración de la marca. `update` escribe `code` y `setting` escribe `validations`; cada uno reemplaza el objeto completo, así que hay que enviar todo.",
 *     @OA\Property(property="code", type="string", description="Código corto de la marca. La ficha técnica exige referencias con el formato `<code>-123456`.", example="LM"),
 *     @OA\Property(property="validations", type="array", description="Reglas para el código de sus productos.", @OA\Items(ref="#/components/schemas/TrademarkValidation"))
 * )
 *
 * @OA\Schema(
 *     schema="Trademark",
 *     type="object",
 *     description="Marca como la entregan `TrademarkCollection` (all) y `TrademarkResource` (el resto). La lista no trae `item_id`.",
 *     @OA\Property(property="id", type="integer", example=99),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=15),
 *     @OA\Property(property="name", type="string", example="LUNA MÍA"),
 *     @OA\Property(property="description", type="string", example="LÍNEA DE JEANS PARA DAMA"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/TrademarkSettings")}),
 *     @OA\Property(property="logo", nullable=true, description="Logo (archivo tipo 2 Archivos multimedia, subtipo 9).", allOf={@OA\Schema(ref="#/components/schemas/File")}),
 *     @OA\Property(property="cover", nullable=true, description="Portada para el catálogo (tipo 2, subtipo 10).", allOf={@OA\Schema(ref="#/components/schemas/File")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 10:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 10:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=15),
 *         @OA\Property(property="name", type="string", example="Marcas"),
 *         @OA\Property(property="description", type="string", example="Listado de marcas.")
 *     ),
 *     @OA\Property(property="group", type="array", description="Grupo de la marca (lista con uno).", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="sizes", type="array", description="Tallas que maneja la marca.", @OA\Items(ref="#/components/schemas/Subitem"))
 * )
 *
 * @OA\Schema(
 *     schema="TrademarkResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="trademark", ref="#/components/schemas/Trademark")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="TrademarkListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="trademarks", type="array", @OA\Items(ref="#/components/schemas/Trademark")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="TrademarkImage",
 *     type="object",
 *     @OA\Property(property="file", type="string", format="binary", description="JPG o PNG de máximo 2 MB."),
 *     @OA\Property(property="photo_type_id", type="integer", description="Tipo de archivo (no se valida). El frontend envía 2.", example=2),
 *     @OA\Property(property="photo_subtype_id", type="integer", description="Subtipo (no se valida). El frontend envía 9 para el logo y 10 para la portada; la marca solo los muestra si coinciden.", example=9)
 * )
 *
 * @OA\Schema(
 *     schema="TrademarkRequest",
 *     type="object",
 *     required={"group_id", "name", "description", "logo"},
 *     description="Se envía como `multipart/form-data` (`logo[file]`, `logo[photo_type_id]`…).",
 *     @OA\Property(property="group_id", type="integer", description="Grupo de la marca.", example=66),
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre las marcas.", example="LUNA MÍA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="LÍNEA DE JEANS PARA DAMA"),
 *     @OA\Property(property="logo", description="Al crear, `logo.file` es obligatorio; al editar, opcional (si llega, se agrega).", allOf={@OA\Schema(ref="#/components/schemas/TrademarkImage")}),
 *     @OA\Property(property="cover", description="Opcional.", allOf={@OA\Schema(ref="#/components/schemas/TrademarkImage")}),
 *     @OA\Property(property="settings", type="object", description="Solo al editar (obligatorio). Al crear no se guarda.", @OA\Property(property="code", type="string", example="LM"))
 * )
 */
class TrademarkController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/trademarks/all",
     *     tags={"Trademarks"},
     *     summary="Listar las marcas",
     *     description="Cada marca trae logo, portada, grupo y tallas. También la pueden consultar quienes configuran procesos, subprocesos u operaciones.",
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
     *         description="Lista de marcas cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TrademarkListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"}, "errors": {"dir": {"Valor inválido. Permitidos: asc, desc."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(TrademarkAllRequest $request)
    {
        try {
            $trademakers = Trademark::with(['group', 'logo', 'cover'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $trademakers = $trademakers->paginate($request->integer('per_page', $trademakers->count()));

            return $this->successResponse(
                new TrademarkCollection($trademakers),
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
     *     path="/trademarks/find/{id}",
     *     tags={"Trademarks"},
     *     summary="Obtener una marca",
     *     description="Trae la marca con logo, portada, grupo y tallas.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Marca cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TrademarkResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(TrademarkFindRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);
            $trademark->load(['group', 'logo', 'cover']);

            return $this->successResponse(
                new TrademarkResource($trademark),
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
     *     path="/trademarks/store",
     *     tags={"Trademarks"},
     *     summary="Crear una marca",
     *     description="Crea la marca, la asocia al grupo y guarda logo y portada en `storage/trademark`. No guarda `settings`: el código se configura después al editar.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/TrademarkRequest"))
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Marca creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TrademarkResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"group_id": "Identificador de Categoría", "name": "Nombre", "description": "Descripción", "logo": "Logo de la Marca", "cover": "Imagen de portada para el catálogo"},
     *                 "errors": {"logo": {"Es obligatorio."}, "name": {"El campo debe estar en mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(TrademarkStoreRequest $request)
    {
        try {
            $trademark = new Trademark;
            $trademark->name = $request->input('name');
            $trademark->description = $request->input('description');
            $trademark->save();

            $trademark->auditSync('group', [$request->integer('group_id')]);

            if ($request->hasFile('logo')) {
                app(FileService::class)->save(
                    $trademark,
                    $request->file('logo.file'),
                    $request->integer('logo.photo_type_id'),
                    $request->integer('logo.photo_subtype_id'),
                    'public',
                    'trademark'
                );
            }

            if ($request->hasFile('cover')) {
                app(FileService::class)->save(
                    $trademark,
                    $request->file('cover.file'),
                    $request->integer('cover.photo_type_id'),
                    $request->integer('cover.photo_subtype_id'),
                    'public',
                    'trademark'
                );
            }

            $trademark->load(['group', 'logo', 'cover']);

            return $this->successResponse(
                new TrademarkResource($trademark),
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
     *     path="/trademarks/update/{id}",
     *     tags={"Trademarks"},
     *     summary="Editar una marca",
     *     description="Reemplaza nombre, descripción, grupo y `settings` (exige `settings.code`; si no se reenvían las `validations`, se pierden). Si llegan imágenes nuevas, se agregan. Para enviar archivos, el frontend usa **POST** a esta misma URL con `_method=PUT`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/TrademarkRequest"),
     *                     @OA\Schema(@OA\Property(property="_method", type="string", enum={"PUT"}, description="Solo si se envía como POST."))
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marca editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TrademarkResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o marca no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del area", "group_id": "Identificador del Grupo", "name": "Nombre", "description": "Descripción", "logo": "Logo de la Marca", "cover": "Imagen de portada para el catálogo"},
     *                 "errors": {"settings.code": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(TrademarkUpdateRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);
            $trademark->name = $request->input('name');
            $trademark->description = $request->input('description');
            $trademark->settings = $request->input('settings');
            $trademark->save();

            $trademark->auditSync('group', [$request->integer('group_id')]);

            if ($request->hasFile('logo.file')) {
                app(FileService::class)->save(
                    $trademark,
                    $request->file('logo.file'),
                    $request->integer('logo.photo_type_id'),
                    $request->integer('logo.photo_subtype_id'),
                    'public',
                    'trademark'
                );
            }

            if ($request->hasFile('cover.file')) {
                app(FileService::class)->save(
                    $trademark,
                    $request->file('cover.file'),
                    $request->integer('cover.photo_type_id'),
                    $request->integer('cover.photo_subtype_id'),
                    'public',
                    'trademark'
                );
            }

            $trademark->load(['group', 'logo', 'cover']);

            return $this->successResponse(
                new TrademarkResource($trademark),
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
     *     path="/trademarks/delete/{id}",
     *     tags={"Trademarks"},
     *     summary="Desactivar una marca",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Marca desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/TrademarkResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(TrademarkDeleteRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);
            $trademark->delete();

            return $this->successResponse(
                new TrademarkResource($trademark),
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
     *     path="/trademarks/restore/{id}",
     *     tags={"Trademarks"},
     *     summary="Restaurar una marca desactivada",
     *     description="Solo acepta marcas desactivadas.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Marca restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TrademarkResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(TrademarkRestoreRequest $request, $id)
    {
        try {
            $trademark = Trademark::withTrashed()->findOrFail($id);
            $trademark->restore();

            return $this->successResponse(
                new TrademarkResource($trademark),
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
     *     path="/trademarks/setting/{id}",
     *     tags={"Trademarks"},
     *     summary="Configurar las reglas de código de una marca",
     *     description="Guarda `settings.validations`: las expresiones regulares que deben cumplir los códigos de los productos de la marca (se usan al crear productos y fichas técnicas). Reemplaza `settings` completo, así que hay que reenviar `code`. Requiere el permiso `trademarks.settings`. El `{id}` no se valida: si no existe, responde 404.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"settings"},
     *             @OA\Property(property="settings", ref="#/components/schemas/TrademarkSettings"),
     *             example={"settings": {"code": "LM", "validations": {{"id": 1, "regex": "/^LM[0-9]{6}$/", "message": "Debe iniciar con LM y tener 6 números", "created_at": "2026-05-14T15:00:00.000Z", "updated_at": null, "deleted_at": null}}}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reglas guardadas correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TrademarkResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Reglas inválidas.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"settings": "Configuración", "settings.validations": "Validaciones"}, "errors": {"settings.validations.1.regex": {"Ya ha sido tomado en la validación n° 1."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function setting(TrademarkSettingRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);
            $trademark->settings = $request->input('settings');

            $trademark->save();

            return $this->successResponse(
                new TrademarkResource($trademark),
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
     *     path="/trademarks/size/assign/{id}",
     *     tags={"Trademarks"},
     *     summary="Asignar una talla a una marca",
     *     description="Agrega la talla a las tallas que maneja la marca.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="object", required={"size_id"}, @OA\Property(property="size_id", type="integer", example=467))
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Talla asignada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TrademarkResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Marca o talla no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"id": "Identificador de la marca", "subcategory_id": "Identificador de la talla"}, "errors": {"size_id": {"No hay ningún registro."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function size_assign(TrademarkSizeAssignRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);

            $trademark->auditAttach('sizes', $request->integer('size_id'));

            $trademark->load(['item', 'sizes']);

            return $this->successResponse(
                new TrademarkResource($trademark),
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
     *     path="/trademarks/size/remove/{id}",
     *     tags={"Trademarks"},
     *     summary="Quitar una talla de una marca",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="object", required={"size_id"}, @OA\Property(property="size_id", type="integer", example=467))
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Talla quitada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TrademarkResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="La marca no tiene esa talla.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"id": "Identificador de la categoría", "subcategory_id": "Identificador de la subcategoría"}, "errors": {"size_id": {"No hay ningún registro."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function size_remove(TrademarkSizeRemoveRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);

            $trademark->auditDetach('sizes', $request->integer('size_id'));

            $trademark->load(['item', 'sizes']);

            return $this->successResponse(
                new TrademarkResource($trademark),
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
