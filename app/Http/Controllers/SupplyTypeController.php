<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\SupplyType\SupplyTypeAllRequest;
use App\Http\Requests\Typification\SupplyType\SupplyTypeDeleteRequest;
use App\Http\Requests\Typification\SupplyType\SupplyTypeFindRequest;
use App\Http\Requests\Typification\SupplyType\SupplyTypeRestoreRequest;
use App\Http\Requests\Typification\SupplyType\SupplyTypeSettingRequest;
use App\Http\Requests\Typification\SupplyType\SupplyTypeStoreRequest;
use App\Http\Requests\Typification\SupplyType\SupplyTypeUpdateRequest;
use App\Http\Resources\Typification\SupplyType\SupplyTypeCollection;
use App\Http\Resources\Typification\SupplyType\SupplyTypeResource;
use App\Models\SupplyType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="SupplyFormField",
 *     type="object",
 *     required={"field", "label", "type"},
 *     description="Campo del formulario de los insumos de este tipo. Al crear o editar un insumo, sus `rules` se aplican al valor que llega en `settings.<field>`.",
 *
 *     @OA\Property(property="id", type="integer", example=2),
 *     @OA\Property(property="field", type="string", pattern="^[a-z]+(_[a-z]+)*$", description="Nombre del campo: minúsculas y guiones bajos. Único en el formulario.", example="width"),
 *     @OA\Property(property="label", type="string", description="Texto visible, en MAYÚSCULAS. Único en el formulario.", example="ANCHO"),
 *     @OA\Property(property="type", type="string", enum={"text", "textarea", "number", "date", "datetime", "boolean", "select", "selectdinamic"}, example="number"),
 *     @OA\Property(property="rules", type="array", description="Reglas de Laravel. Se revisa que `min`/`max` sean números coherentes, que `after`/`before` sean fechas válidas, que un `select` tenga `in:` y un `selectdinamic`, `exists:`.", @OA\Items(type="string"), example={"numeric", "min:1", "max:1000", "required"}),
 *     @OA\Property(property="model", type="string", description="Solo `selectdinamic`: modelo de donde salen las opciones (ver `model_types`).", example="App\Models\Supplier"),
 *     @OA\Property(property="cardinality", type="string", enum={"single", "multiple"}, description="Solo `selectdinamic`: una o varias opciones."),
 *     @OA\Property(property="path", type="string", description="Solo `selectdinamic`: dato que se muestra de cada opción (por ejemplo `name` o `settings.code`).", example="name"),
 *     @OA\Property(property="param", type="string", description="Solo `selectdinamic`: modelo por el que se filtran las opciones (por ejemplo `App\Models\SupplierType`), con su id en `supplier_type_id` o `supply_type_id`.")
 * )
 *
 * @OA\Schema(
 *     schema="SupplyTypeSettings",
 *     type="object",
 *     description="Configuración del tipo de insumo. `store`/`update` escriben las banderas y `setting` escribe `form`; cada uno reemplaza el objeto completo, así que hay que enviar todo (el frontend mezcla lo que ya había).",
 *
 *     @OA\Property(property="has_supplies", type="boolean", description="Tiene insumos propios.", example=true),
 *     @OA\Property(property="in_technical_sheet", type="boolean", description="Aplica a la ficha técnica.", example=false),
 *     @OA\Property(property="in_production_order", type="boolean", description="Aplica a la orden de producción.", example=true),
 *     @OA\Property(property="paragraph", type="string", nullable=true, enum={"fabric", "roll"}, description="Apartado de la orden de producción: `fabric` (tela) o `roll` (rollos). Obligatorio si `in_production_order` es `true`.", example="roll"),
 *     @OA\Property(property="form", type="array", description="Formulario de sus insumos (lo escribe `setting`).", @OA\Items(ref="#/components/schemas/SupplyFormField"))
 * )
 *
 * @OA\Schema(
 *     schema="SupplyType",
 *     type="object",
 *     description="Tipo de insumo (TELA, HILO, ROLLO…). Su `settings` dice dónde se usa y, en `form`, qué campos se piden al crear sus insumos.",
 *
 *     @OA\Property(property="id", type="integer", example=420),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=28),
 *     @OA\Property(property="name", type="string", example="BOTONES"),
 *     @OA\Property(property="description", type="string", example="BOTONES METÁLICOS PARA JEAN"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/SupplyTypeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 16:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 16:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=28),
 *         @OA\Property(property="name", type="string", example="Tipos de Insumo"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de insumo."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SupplyTypeResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="supply_type", ref="#/components/schemas/SupplyType"),
 *         @OA\Property(property="model_types", type="object", description="Modelos que se pueden usar en un campo `selectdinamic`, con su etiqueta, modelo, URL de la API para cargar opciones y campo a mostrar.", additionalProperties=@OA\AdditionalProperties(type="object", @OA\Property(property="label", type="string"), @OA\Property(property="model", type="string"), @OA\Property(property="url", type="string"), @OA\Property(property="value", type="string"), @OA\Property(property="option", type="string")), example={"sizes": {"label": "Tallas", "model": "App\Models\Size", "url": "/sizes/all", "option": "name"}})
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SupplyTypeListResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="supply_types", type="array", @OA\Items(allOf={
 *
 *             @OA\Schema(ref="#/components/schemas/SupplyType"),
 *             @OA\Schema(@OA\Property(property="supplies", type="array", description="Insumos del tipo, serializados por Eloquent.", @OA\Items(type="object", additionalProperties=true)))
 *         })),
 *
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SupplyTypeRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de insumo.", example="BOTONES"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="BOTONES METÁLICOS PARA JEAN"),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         required={"has_supplies", "in_technical_sheet", "in_production_order"},
 *         @OA\Property(property="has_supplies", type="boolean", example=true),
 *         @OA\Property(property="in_technical_sheet", type="boolean", example=true),
 *         @OA\Property(property="in_production_order", type="boolean", example=false),
 *         @OA\Property(property="paragraph", type="string", nullable=true, enum={"fabric", "roll"}, example=null)
 *     )
 * )
 */
class SupplyTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/supply_types/all",
     *     tags={"Typification › Supply Types"},
     *     summary="Listar los tipos de insumo",
     *     description="Cada tipo trae sus insumos (`supplies`), salvo con `without_supplies`. También la pueden consultar quienes configuran procesos, subprocesos u operaciones.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="in_technical_sheet",
     *         in="query",
     *         required=false,
     *         description="Filtra por `settings.in_technical_sheet` (`true`/`1` o `false`/`0`). Si no se envía, no filtra.",
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="in_production_order",
     *         in="query",
     *         required=false,
     *         description="Filtra por `settings.in_production_order`. Si no se envía, no filtra.",
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="without_supplies",
     *         in="query",
     *         required=false,
     *         description="Con `true`/`1` los tipos llegan sin `supplies`. Lo usa el catálogo de los campos `selectdinamic`, que solo muestra los tipos.",
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *
     *         @OA\Schema(type="string", enum={"id", "name", "description", "created_at", "updated_at", "deleted_at"}, example="name")
     *     ),
     *
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(ref="#/components/parameters/WithTrashedOptional"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tipos de insumo cargada correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/SupplyTypeListResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"dir": {"Valor inválido. Permitidos: asc, desc."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(SupplyTypeAllRequest $request)
    {
        try {
            $supply_types = SupplyType::with(['item'])
                ->when(! $request->boolean('without_supplies'), function ($query) {
                    return $query->with(['supplies']);
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('in_technical_sheet'), function ($query) use ($request) {
                    return $query->where('settings->in_technical_sheet', $request->boolean('in_technical_sheet'));
                })
                ->when($request->filled('in_production_order'), function ($query) use ($request) {
                    return $query->where('settings->in_production_order', $request->boolean('in_production_order'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $supply_types = $supply_types->paginate($request->integer('per_page', $supply_types->count()));

            return $this->successResponse(
                new SupplyTypeCollection($supply_types),
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
     *     path="/typification/supply_types/find/{id}",
     *     tags={"Typification › Supply Types"},
     *     summary="Obtener un tipo de insumo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de insumo cargado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/SupplyTypeResponse")
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(SupplyTypeFindRequest $request, $id)
    {
        try {
            $supply_type = SupplyType::with(['supplies'])
                ->findOrFail($id);

            return $this->successResponse(
                new SupplyTypeResource($supply_type),
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
     *     path="/typification/supply_types/store",
     *     tags={"Typification › Supply Types"},
     *     summary="Crear un tipo de insumo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/SupplyTypeRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de insumo creado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/SupplyTypeResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.has_supplies": "Tiene insumos", "settings.in_technical_sheet": "Aplica a Ficha Técnica"},
     *                 "errors": {"settings.has_supplies": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(SupplyTypeStoreRequest $request)
    {
        try {
            $supply_type = new SupplyType;
            $supply_type->name = $request->input('name');
            $supply_type->description = $request->input('description');
            $supply_type->settings = $request->input('settings');

            $supply_type->save();

            return $this->successResponse(
                new SupplyTypeResource($supply_type),
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
     *     path="/typification/supply_types/update/{id}",
     *     tags={"Typification › Supply Types"},
     *     summary="Editar un tipo de insumo",
     *     description="Reemplaza nombre, descripción y `settings` completo: si no se envía `form`, el formulario de sus insumos se pierde (ver pendientes).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/SupplyTypeRequest",
     *             example={"name": "BOTONES", "description": "BOTONES Y REMACHES METÁLICOS", "settings": {"has_supplies": true, "in_technical_sheet": true, "in_production_order": false}}
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de insumo editado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/SupplyTypeResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de insumo no registrado.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de tela", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.has_supplies": "Tiene insumos", "settings.in_technical_sheet": "Aplica a Ficha Técnica"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(SupplyTypeUpdateRequest $request, $id)
    {
        try {
            $supply_type = SupplyType::findOrFail($id);
            $supply_type->name = $request->input('name');
            $supply_type->description = $request->input('description');
            $supply_type->settings = $request->input('settings');
            $supply_type->save();

            return $this->successResponse(
                new SupplyTypeResource($supply_type),
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
     *     path="/typification/supply_types/delete/{id}",
     *     tags={"Typification › Supply Types"},
     *     summary="Desactivar un tipo de insumo",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de insumo desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *
     *         @OA\JsonContent(ref="#/components/schemas/SupplyTypeResponse")
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(SupplyTypeDeleteRequest $request, $id)
    {
        try {
            $supply_type = SupplyType::findOrFail($id);
            $supply_type->delete();

            return $this->successResponse(
                new SupplyTypeResource($supply_type),
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
     *     path="/typification/supply_types/restore/{id}",
     *     tags={"Typification › Supply Types"},
     *     summary="Restaurar un tipo de insumo desactivado",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de insumo restaurado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/SupplyTypeResponse")
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(SupplyTypeRestoreRequest $request, $id)
    {
        try {
            $supply_type = SupplyType::withTrashed()->findOrFail($id);
            $supply_type->restore();

            return $this->successResponse(
                new SupplyTypeResource($supply_type),
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
     *     path="/typification/supply_types/setting/{id}",
     *     tags={"Typification › Supply Types"},
     *     summary="Configurar el formulario de los insumos de un tipo",
     *     description="Guarda `settings.form`: los campos que se piden al crear o editar los insumos de este tipo. Reemplaza `settings` completo, así que hay que enviar también las banderas (`has_supplies`, `in_technical_sheet`, `in_production_order`, `paragraph`); el frontend las mezcla con lo que ya había. Requiere el permiso `typification.supply_types.settings`. El `{id}` no se valida: si no existe, responde 404.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *             required={"settings"},
     *
     *             @OA\Property(property="settings", ref="#/components/schemas/SupplyTypeSettings"),
     *             example={"settings": {"has_supplies": true, "in_technical_sheet": false, "in_production_order": true, "paragraph": "roll", "form": {{"id": 1, "field": "width", "label": "ANCHO", "type": "number", "rules": {"numeric", "required"}}, {"id": 2, "field": "meters", "label": "METROS", "type": "number", "rules": {"numeric", "required"}}, {"id": 3, "field": "supplier_id", "label": "PROVEEDOR", "type": "selectdinamic", "model": "App\Models\Supplier", "param": "App\Models\SupplierType", "supplier_type_id": "424", "path": "name", "cardinality": "single", "rules": {"exists:subitems,id,item_id,App\Models\Supplier::ITEM_ID", "required"}}}}}
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Configuración guardada correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/SupplyTypeResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Formulario inválido.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"settings": "Configuración", "settings.schema": "Esquema", "settings.schema.dinamic": "Estructura dinámica", "settings.schema.static": "Estructura estática"},
     *                 "errors": {"settings.form.1.label": {"El nombre visible del campo ya ha sido tomado."}, "settings.form.0.rules.min": {"El mínimo no puede ser mayor o igual al maximo."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function setting(SupplyTypeSettingRequest $request, $id)
    {
        try {
            $supply_type = SupplyType::findOrFail($id);
            $supply_type->settings = $request->input('settings');

            $supply_type->save();

            return $this->successResponse(
                new SupplyTypeResource($supply_type),
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
