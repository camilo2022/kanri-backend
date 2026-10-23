<?php

namespace App\Http\Controllers;

use App\Http\Requests\Collection\CollectionAllRequest;
use App\Http\Requests\Collection\CollectionDeleteRequest;
use App\Http\Requests\Collection\CollectionFindRequest;
use App\Http\Requests\Collection\CollectionRestoreRequest;
use App\Http\Requests\Collection\CollectionStoreRequest;
use App\Http\Requests\Collection\CollectionUpdateRequest;
use App\Http\Resources\Collection\CollectionCollection;
use App\Http\Resources\Collection\CollectionResource;
use App\Models\Collection;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Collections",
 *     description="Endpoints para gestionar colecciones"
 * )
 *
 * @OA\Schema(
 *     schema="CollectionSettings",
 *     type="object",
 *     required={"code", "start_date", "end_date"},
 *     description="Código y vigencia de la colección. El código es único y el rango de fechas no puede cruzarse con el de otra colección activa.",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos.", example="32026"),
 *     @OA\Property(property="start_date", type="string", format="date", description="Formato Y-m-d.", example="2027-01-15"),
 *     @OA\Property(property="end_date", type="string", format="date", description="Formato Y-m-d. Debe ser posterior a `start_date` (ver pendientes: hoy no se comprueba).", example="2027-03-31")
 * )
 *
 * @OA\Schema(
 *     schema="Collection",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=118),
 *     @OA\Property(property="item_id", type="integer", example=30),
 *     @OA\Property(property="name", type="string", example="C32026"),
 *     @OA\Property(property="description", type="string", example="COLECCIÓN PRIMAVERA DENIM"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/CollectionSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 12:30:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 12:30:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=30),
 *         @OA\Property(property="name", type="string", example="Colección"),
 *         @OA\Property(property="description", type="string", example="Listado de colecciones."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CollectionDetail",
 *     description="Colección como la entrega `CollectionResource`: incluye sus fichas técnicas.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Collection"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="technical_sheets",
 *                 type="array",
 *                 description="Fichas técnicas de la colección. En `find` cada ficha trae además `technical_sheet_details`, `product` (con `trademark.sizes` y `subcategory.category`), `garment_type`, `wash_tone`, `boot_type`, `photo_d`, `photo_t`, `products` y `production_orders` (con detalles, cantidades, tela, color, proveedor y reasignaciones). En store/update/delete/restore vienen sin relaciones.",
 *                 @OA\Items(
 *                     type="object",
 *                     additionalProperties=true,
 *                     @OA\Property(property="id", type="integer", example=905),
 *                     @OA\Property(property="uuid", type="string", format="uuid", example="3f6d2a1c-8b4e-4c7a-9e21-6a5b0d4c2f18"),
 *                     @OA\Property(property="code", type="string", example="CU85"),
 *                     @OA\Property(property="collection_id", type="integer", example=118),
 *                     @OA\Property(property="product_id", type="integer", example=1460),
 *                     @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado", "Cancelado"}, example="En revision")
 *                 )
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="CollectionResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="collection", ref="#/components/schemas/CollectionDetail"),
 *         @OA\Property(
 *             property="status_production_order",
 *             type="array",
 *             description="Catálogo fijo de estados de una orden de producción.",
 *             @OA\Items(type="string"),
 *             example={"Pendiente", "Cancelado", "Aprobado"}
 *         )
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="CollectionListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="collections", type="array", @OA\Items(ref="#/components/schemas/Collection")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="CollectionRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         pattern="^C\d(19\d{2}|20([0-1]\d|2[0-6]))$",
 *         description="`C`, un dígito y el año (de 1900 a 2026; ver pendientes). Único entre las colecciones.",
 *         example="C32026"
 *     ),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="COLECCIÓN PRIMAVERA DENIM"),
 *     @OA\Property(property="settings", ref="#/components/schemas/CollectionSettings")
 * )
 */
class CollectionController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/collections/all",
     *     tags={"Collections"},
     *     summary="Listar las colecciones",
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
     *         description="Lista de colecciones cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CollectionListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"page": {"Debe ser un valor numérico."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(CollectionAllRequest $request)
    {
        try {
            $collections = Collection::with(['item'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $collections = $collections->paginate($request->integer('per_page', $collections->count()));

            return $this->successResponse(
                new CollectionCollection($collections),
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
     *     path="/collections/find/{id}",
     *     tags={"Collections"},
     *     summary="Obtener una colección con sus fichas técnicas",
     *     description="Devuelve la colección con sus fichas técnicas, productos y órdenes de producción, y el catálogo de estados de orden.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Colección cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CollectionResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(CollectionFindRequest $request, $id)
    {
        try {
            $collection = Collection::with(['item', 'technical_sheets' => ['technical_sheet_details', 'product' => ['trademark' => ['sizes'], 'subcategory' => ['category']], 'garment_type', 'wash_tone', 'boot_type',  'photo_d', 'photo_t', 'products', 'production_orders' => ['production_order_details' => ['production_order_detail_quantities', 'model'], 'fabric' => ['model'], 'color', 'supplier', 'reassignments']]])
                ->findOrFail($id);

            return $this->successResponse(
                new CollectionResource($collection),
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
     *     path="/collections/store",
     *     tags={"Collections"},
     *     summary="Crear una colección",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CollectionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Colección creada correctamente. `technical_sheets` llega vacío.",
     *         @OA\JsonContent(ref="#/components/schemas/CollectionResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos, código repetido o rango de fechas cruzado con otra colección.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings.code": "Código", "settings.start_date": "Fecha de Inicio", "settings.end_date": "Fecha de Fin"},
     *                 "errors": {"name": {"Formato inválido. Debe iniciar con C y contener 5 números (los últimos 4 corresponden al año)."}, "settings.start_date": {"Ya existe una colección en ese rango de fechas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(CollectionStoreRequest $request)
    {
        try {
            $collection = new Collection;
            $collection->name = $request->input('name');
            $collection->description = $request->input('description');
            $collection->settings = $request->input('settings');

            $collection->save();

            return $this->successResponse(
                new CollectionResource($collection),
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
     *     path="/collections/update/{id}",
     *     tags={"Collections"},
     *     summary="Editar una colección",
     *     description="**Hoy solo guarda `name` y `description`.** `settings` se valida (código único, fechas sin cruce) pero no se guarda (ver pendientes). Responde 201, no 200.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/CollectionRequest",
     *             example={"name": "C42026", "description": "COLECCIÓN INVIERNO DENIM", "settings": {"code": "42026", "start_date": "2027-04-01", "end_date": "2027-06-30"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Colección editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CollectionResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos, colección no registrada, código repetido o rango de fechas cruzado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador de la colección", "name": "Nombre", "description": "Descripción", "settings.code": "Código", "settings.start_date": "Fecha de Inicio", "settings.end_date": "Fecha de Fin"},
     *                 "errors": {"settings.code": {"El código ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(CollectionUpdateRequest $request, $id)
    {
        try {
            $collection = Collection::findOrFail($id);
            $collection->name = $request->input('name');
            $collection->description = $request->input('description');

            $collection->save();

            return $this->successResponse(
                new CollectionResource($collection),
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
     * @OA\Delete(
     *     path="/collections/delete/{id}",
     *     tags={"Collections"},
     *     summary="Desactivar una colección",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Colección desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/CollectionResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(CollectionDeleteRequest $request, $id)
    {
        try {
            $collection = Collection::findOrFail($id);
            $collection->delete();

            return $this->successResponse(
                new CollectionResource($collection),
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
     *     path="/collections/restore/{id}",
     *     tags={"Collections"},
     *     summary="Restaurar una colección desactivada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Colección restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CollectionResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(CollectionRestoreRequest $request, $id)
    {
        try {
            $collection = Collection::withTrashed()->findOrFail($id);
            $collection->restore();

            return $this->successResponse(
                new CollectionResource($collection),
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
