<?php

namespace App\Http\Controllers;

use App\Exports\SupplyExport;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyAllRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyDeleteRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyExcelRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyFindRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyRestoreRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplySaveRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyStoreRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyUpdateRequest;
use App\Http\Resources\Typification\SupplyType\Supply\SupplyCollection;
use App\Http\Resources\Typification\SupplyType\Supply\SupplyResource;
use App\Imports\ImportErrorReport;
use App\Imports\SupplyImport;
use App\Models\Supply;
use App\Models\SupplyType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @OA\Schema(
 *     schema="SupplySettings",
 *     type="object",
 *     description="Configuración del insumo. Se guarda tal cual llega. El frontend guarda en `values` los campos simples del formulario de su tipo (los `selectdinamic` van como relaciones, no aquí).",
 *     @OA\Property(property="values", type="object", additionalProperties=true, example={"width": 150, "heigth": 1200})
 * )
 *
 * @OA\Schema(
 *     schema="Supply",
 *     type="object",
 *     description="Insumo como lo entrega `SupplyCollection` (all). Además de estos campos, por cada campo `selectdinamic` del formulario de su tipo trae una clave con el nombre del campo (por ejemplo `supplier_id`) con el registro relacionado, o la lista si es `multiple`.",
 *     @OA\Property(property="id", type="integer", example=488),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=29),
 *         @OA\Property(property="name", type="string", example="Insumos")
 *     ),
 *     @OA\Property(property="name", type="string", example="DENIM STRETCH 12 OZ"),
 *     @OA\Property(property="description", type="string", example="DENIM AZUL ÍNDIGO CON ELASTANO"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 10:15:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 10:15:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="settings", ref="#/components/schemas/SupplySettings"),
 *     @OA\Property(property="production_order_details", type="array", description="Detalles de órdenes de producción donde se usa el insumo.", @OA\Items(type="object", additionalProperties=true))
 * )
 *
 * @OA\Schema(
 *     schema="SupplyDetail",
 *     type="object",
 *     description="Insumo como lo entrega `SupplyResource` (find, store, update, delete, restore). Por cada campo `selectdinamic` del formulario de su tipo trae dos claves: el modelo en minúsculas (`supplier`, o en plural si es `multiple`, como `colors`) con el registro, y el nombre del campo (`supplier_id`) con el id o la lista de ids.",
 *     @OA\Property(property="id", type="integer", example=488),
 *     @OA\Property(property="item_id", type="integer", example=29),
 *     @OA\Property(property="name", type="string", example="DENIM STRETCH 12 OZ"),
 *     @OA\Property(property="description", type="string", example="DENIM AZUL ÍNDIGO CON ELASTANO"),
 *     @OA\Property(property="settings", ref="#/components/schemas/SupplySettings"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 10:15:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 10:15:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="item", type="object", additionalProperties=true),
 *     @OA\Property(property="supply_type", description="Su tipo de insumo, con el formulario en `settings.form`.", allOf={@OA\Schema(ref="#/components/schemas/Subitem")}),
 *     @OA\Property(property="supplier", type="object", additionalProperties=true, description="Ejemplo de relación dinámica (tipo TELA, campo `supplier_id`)."),
 *     @OA\Property(property="supplier_id", type="integer", description="Ejemplo de relación dinámica: id del proveedor.", example=470)
 * )
 *
 * @OA\Schema(
 *     schema="SupplyResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="supply", ref="#/components/schemas/SupplyDetail"),
 *         @OA\Property(property="model_types", type="object", description="Modelos que se pueden usar en los campos `selectdinamic` (igual que en tipos de insumo).", additionalProperties=true)
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SupplyListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="supplies", type="array", @OA\Items(ref="#/components/schemas/Supply")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SupplyRequest",
 *     type="object",
 *     required={"supply_type_id", "name", "description"},
 *     description="Además de los campos fijos, se validan los del formulario del tipo de insumo (`settings.form`) con sus propias reglas: los campos simples en `values.<campo>` y los `selectdinamic` en `class.<campo>` (un id, o una lista si son `multiple`).",
 *     @OA\Property(property="supply_type_id", type="integer", description="Tipo de insumo. Al crear se asocia; al editar no es obligatorio, pero sin él no se validan los campos del formulario.", example=218),
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los insumos.", example="DENIM STRETCH 12 OZ"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="DENIM AZUL ÍNDIGO CON ELASTANO"),
 *     @OA\Property(property="values", type="object", additionalProperties=true, description="Campos simples del formulario (se validan, pero no se guardan por sí solos: el frontend los copia a `settings.values`).", example={"width": 150, "heigth": 1200}),
 *     @OA\Property(property="class", type="object", additionalProperties=true, description="Campos `selectdinamic`: se guardan como relaciones del insumo.", example={"supplier_id": 470, "color_id": {512, 515}}),
 *     @OA\Property(property="settings", ref="#/components/schemas/SupplySettings")
 * )
 */
class SupplyController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/supply_types/supplies/all/{supply_type_id}",
     *     tags={"Typification › Supplies"},
     *     summary="Listar los insumos de un tipo de insumo",
     *     description="Cada insumo trae `item`, `settings`, sus detalles de órdenes de producción y sus relaciones dinámicas. También la pueden consultar quienes configuran procesos, subprocesos u operaciones. Hoy responde **500** si el tipo de insumo tiene campos `selectdinamic` (TELA, ROLLO…), porque el modelo `Supply` no tiene el método `related()` (ver pendientes).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supply_type_id",
     *         in="path",
     *         required=true,
     *         description="Tipo de insumo.",
     *         @OA\Schema(type="integer", example=218)
     *     ),
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
     *         description="Lista de insumos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplyListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"}, "errors": {"column": {"Valor inválido. Permitidos: id, name, description, created_at, updated_at, deleted_at."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(SupplyAllRequest $request, $supply_type_id)
    {
        try {
            $supplies = Supply::with(['supply_type', 'production_order_details'])
                ->whereHas('supply_type', fn ($r) => $r->where('id', $supply_type_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $supplies = $supplies->paginate($request->integer('per_page', $supplies->count()));

            logger()->debug('DATA', ['SUPPLY_TYPE' => $supply_type_id, 'RESULT' => $supplies]);

            return $this->successResponse(
                new SupplyCollection($supplies),
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
     *     path="/typification/supply_types/supplies/find/{id}",
     *     tags={"Typification › Supplies"},
     *     summary="Obtener un insumo",
     *     description="Trae el insumo con su tipo, sus relaciones dinámicas y `model_types`. Hoy responde **500** si el tipo de insumo tiene campos `selectdinamic` (TELA, ROLLO…), porque el modelo `Supply` no tiene el método `related()` (ver pendientes).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Insumo cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplyResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(SupplyFindRequest $request, $id)
    {
        try {
            $supply = Supply::with(['supply_type'])->findOrFail($id);

            return $this->successResponse(
                new SupplyResource($supply),
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
     *     path="/typification/supply_types/supplies/store",
     *     tags={"Typification › Supplies"},
     *     summary="Crear un insumo",
     *     description="Crea el insumo, lo asocia a su tipo y guarda como relaciones los campos `selectdinamic` que lleguen en `class`. Hoy responde **500** si el tipo de insumo tiene campos `selectdinamic` (TELA, ROLLO…), porque el modelo `Supply` no tiene el método `related()` (ver pendientes).",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/SupplyRequest",
     *             example={"supply_type_id": 218, "name": "DENIM STRETCH 12 OZ", "description": "DENIM AZUL ÍNDIGO CON ELASTANO", "values": {"width": 150, "heigth": 1200}, "class": {"supplier_id": 470, "color_id": {512, 515}}, "settings": {"values": {"width": 150, "heigth": 1200}}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Insumo creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplyResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos (campos fijos o del formulario del tipo).",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"supply_type_id": "Identificador del tipo de insumo", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"values.width": {"Es obligatorio."}, "class.supplier_id": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(SupplyStoreRequest $request)
    {
        try {
            $supply = new Supply;
            $supply->name = $request->input('name');
            $supply->description = $request->input('description');
            $supply->settings = $request->input('settings');
            $supply->save();

            $supply->auditSync('supply_type', $request->integer('supply_type_id'));

            $supply->load(['supply_type']);

            collect($supply->supply_type->first()?->settings?->form ?? [])
                ->where('type', 'selectdinamic')
                ->each(function ($relationship) use ($supply, $request) {
                    if ($request->has("class.$relationship->field")) {
                        $supply->related($relationship->model)
                            ->sync(Arr::wrap($request->input("class.$relationship->field")));
                    }
                });

            return $this->successResponse(
                new SupplyResource($supply),
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
     *     path="/typification/supply_types/supplies/update/{id}",
     *     tags={"Typification › Supplies"},
     *     summary="Editar un insumo",
     *     description="Reemplaza nombre, descripción y `settings`, y sincroniza las relaciones de `class` que lleguen. No cambia el tipo de insumo. Hoy responde **500** si el tipo de insumo tiene campos `selectdinamic` (TELA, ROLLO…), porque el modelo `Supply` no tiene el método `related()` (ver pendientes).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/SupplyRequest",
     *             example={"supply_type_id": 218, "name": "DENIM STRETCH 12 OZ", "description": "DENIM AZUL ÍNDIGO CON ELASTANO Y LAVADO OSCURO", "values": {"width": 150, "heigth": 1000}, "class": {"supplier_id": 470, "color_id": {512}}, "settings": {"values": {"width": 150, "heigth": 1000}}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Insumo editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplyResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o insumo no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del insumo", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(SupplyUpdateRequest $request, $id)
    {
        try {
            $supply = Supply::findOrFail($id);
            $supply->name = $request->input('name');
            $supply->description = $request->input('description');
            $supply->settings = $request->input('settings');
            $supply->save();

            $supply->load(['supply_type']);

            collect($supply->supply_type->first()?->settings?->form ?? [])
                ->where('type', 'selectdinamic')
                ->each(function ($relationship) use ($supply, $request) {

                    if ($request->has("class.$relationship->field")) {
                        $supply->related($relationship->model)
                            ->sync(Arr::wrap($request->input("class.$relationship->field")));
                    }
                });

            return $this->successResponse(
                new SupplyResource($supply),
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
     *     path="/typification/supply_types/supplies/delete/{id}",
     *     tags={"Typification › Supplies"},
     *     summary="Desactivar un insumo",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse. Hoy responde **500** si el tipo de insumo tiene campos `selectdinamic` (TELA, ROLLO…), porque el modelo `Supply` no tiene el método `related()` (ver pendientes).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Insumo desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/SupplyResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(SupplyDeleteRequest $request, $id)
    {
        try {
            $supply = Supply::findOrFail($id);
            $supply->delete();

            return $this->successResponse(
                new SupplyResource($supply),
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
     *     path="/typification/supply_types/supplies/restore/{id}",
     *     tags={"Typification › Supplies"},
     *     summary="Restaurar un insumo desactivado",
     *     description="Solo acepta insumos desactivados. Hoy responde **500** si el tipo de insumo tiene campos `selectdinamic` (TELA, ROLLO…), porque el modelo `Supply` no tiene el método `related()` (ver pendientes).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Insumo restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplyResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(SupplyRestoreRequest $request, $id)
    {
        try {
            $supply = Supply::withTrashed()->findOrFail($id);
            $supply->restore();

            return $this->successResponse(
                new SupplyResource($supply),
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
     *     path="/typification/supply_types/supplies/export/{supply_type_id}",
     *     tags={"Typification › Supplies"},
     *     summary="Exportar a Excel los insumos de un tipo",
     *     description="Descarga `supplys.xlsx`: una hoja con los insumos del tipo y, por cada campo `selectdinamic` múltiple del formulario, una hoja con sus relaciones. Sirve también como plantilla para importar.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supply_type_id",
     *         in="path",
     *         required=true,
     *         description="Tipo de insumo.",
     *         @OA\Schema(type="integer", example=218)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Archivo de Excel.",
     *         @OA\MediaType(mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Tipo de insumo no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"supply_type_id": "Identificador del tipo de insumo"}, "errors": {"supply_type_id": {"No hay ningún registro."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function export(SupplyExcelRequest $request, $supply_type_id)
    {
        return Excel::download(new SupplyExport($supply_type_id), 'supplys.xlsx');
    }

    /**
     * @OA\Post(
     *     path="/typification/supply_types/supplies/import/{supply_type_id}",
     *     tags={"Typification › Supplies"},
     *     summary="Importar insumos desde Excel",
     *     description="Lee el archivo (mismo formato que la exportación), valida todas las filas y, si no hay errores, crea o actualiza cada insumo por su nombre dentro de una transacción. Si hay errores, responde 422 con un reporte en texto (`file`, ruta en el disco `public`). **Hoy siempre responde 422**: el controlador valida la lista con la clave `supplys` y las reglas esperan `supplies` (ver pendientes). El archivo no se valida y el `{supply_type_id}` inexistente responde 404.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supply_type_id",
     *         in="path",
     *         required=true,
     *         description="Tipo de insumo.",
     *         @OA\Schema(type="integer", example=218)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(type="object", required={"file"}, @OA\Property(property="file", type="string", format="binary", description="Archivo .xlsx."))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Insumos importados. `data` es la lista de filas leídas.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object", additionalProperties=true)),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="La importación tiene errores.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="La importación contiene errores."),
     *             @OA\Property(property="errors", type="object", additionalProperties=@OA\AdditionalProperties(type="array", @OA\Items(type="string")), example={"supplies": {"Es obligatorio."}}),
     *             @OA\Property(property="file", type="string", description="Reporte de errores en el disco `public`.", example="imports/errors_20260514_101500.txt")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function import(Request $request, $supply_type_id)
    {
        try {
            $supply_type = SupplyType::findOrFail($supply_type_id);

            $import = new SupplyImport($supply_type);

            Excel::import($import, $request->file('file'));

            $formRequest = new SupplySaveRequest;

            $supplys = $import->builder()->build();

            $duplicates = $import->builder()->duplicateSupplys();

            $validator = Validator::make(
                [
                    'supply_type_id' => $supply_type_id,
                    'supplys' => $supplys,
                ],
                $formRequest->rules(),
                $formRequest->messages(),
                $formRequest->attributes()
            );

            $formRequest->duplicates = $duplicates;

            $formRequest->setContainer(app());

            $formRequest->withValidator($validator);

            if ($validator->fails()) {
                $path = app(ImportErrorReport::class)->generate(
                    $validator->errors()->toArray(),
                    $supplys,
                    $supply_type
                );

                return response()->json([
                    'message' => 'La importación contiene errores.',
                    'errors' => $validator->errors(),
                    'file' => $path,
                ], 422);
            }

            DB::transaction(function () use ($supplys, $supply_type_id) {
                foreach ($supplys as $item) {
                    $supply = Supply::updateOrCreate(
                        ['name' => $item['name']],
                        [
                            'description' => $item['description'],
                            'settings' => $item['settings'] ?? null,
                        ]
                    );

                    $supply->auditSync('supply_type', $supply_type_id);

                    $supply->load(['supply_type']);

                    collect($supply->supply_type->first()?->settings?->form ?? [])
                        ->where('type', 'selectdinamic')
                        ->each(function ($relationship) use ($supply, $item) {
                            if (Arr::has($item, "class.$relationship->field")) {
                                $supply->related($relationship->model)
                                    ->sync(
                                        Arr::wrap(
                                            Arr::get($item, "class.$relationship->field")
                                        )
                                    );
                            }
                        });
                }
            });

            return $this->successResponse(
                $supplys,
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
