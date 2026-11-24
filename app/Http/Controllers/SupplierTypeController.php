<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\SupplierType\SupplierTypeAllRequest;
use App\Http\Requests\Typification\SupplierType\SupplierTypeDeleteRequest;
use App\Http\Requests\Typification\SupplierType\SupplierTypeFindRequest;
use App\Http\Requests\Typification\SupplierType\SupplierTypeRestoreRequest;
use App\Http\Requests\Typification\SupplierType\SupplierTypeStoreRequest;
use App\Http\Requests\Typification\SupplierType\SupplierTypeUpdateRequest;
use App\Http\Resources\Typification\SupplierType\SupplierTypeCollection;
use App\Http\Resources\Typification\SupplierType\SupplierTypeResource;
use App\Models\SupplierType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="SupplierTypeSettings",
 *     type="object",
 *     required={"has_person", "has_account_bank", "in_production_order"},
 *     description="Configuración del tipo de proveedor. Se guarda tal cual llega (reemplaza la anterior).",
 *     @OA\Property(property="has_person", type="boolean", description="Sus proveedores llevan datos de persona (contacto).", example=true),
 *     @OA\Property(property="has_account_bank", type="boolean", description="Sus proveedores llevan cuenta bancaria.", example=true),
 *     @OA\Property(property="in_production_order", type="boolean", description="Sus proveedores aparecen en la orden de producción.", example=true),
 *     @OA\Property(property="paragraph", type="string", enum={"production"}, description="Apartado de la orden de producción donde aparecen. Obligatorio si `in_production_order` es `true`. Con `production`, el frontend lo usa como el tipo de los satélites en Gestión de producción.", example="production")
 * )
 *
 * @OA\Schema(
 *     schema="SupplierType",
 *     type="object",
 *     description="Tipo de proveedor (SERVICIO, MATERIA PRIMA…). Su `settings` decide qué datos se piden al crear sus proveedores y si aparecen en la orden de producción.",
 *     @OA\Property(property="id", type="integer", example=425),
 *     @OA\Property(property="item_id", type="integer", example=27),
 *     @OA\Property(property="name", type="string", example="LAVANDERÍA"),
 *     @OA\Property(property="description", type="string", example="PROVEEDORES DE LAVADO Y TINTURA DE PRENDAS"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/SupplierTypeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 13:40:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 13:40:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=27),
 *         @OA\Property(property="name", type="string", example="Tipos de Proveedores"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de proveedores."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SupplierTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="supplier_type", allOf={
 *         @OA\Schema(ref="#/components/schemas/SupplierType"),
 *         @OA\Schema(@OA\Property(property="process", nullable=true, example=null, description="Siempre `null`: el modelo no tiene esa relación (ver pendientes)."))
 *     })),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SupplierTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="supplier_types", type="array", @OA\Items(ref="#/components/schemas/SupplierType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SupplierTypeRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de proveedor.", example="LAVANDERÍA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="PROVEEDORES DE LAVADO Y TINTURA DE PRENDAS"),
 *     @OA\Property(property="settings", ref="#/components/schemas/SupplierTypeSettings")
 * )
 */
class SupplierTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/supplier_types/all",
     *     tags={"Typification › Supplier Types"},
     *     summary="Listar los tipos de proveedor",
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
     *         description="Lista de tipos de proveedor cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierTypeListResponse")
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
    public function all(SupplierTypeAllRequest $request)
    {
        try {
            $supplier_type_types = SupplierType::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $supplier_type_types = $supplier_type_types->paginate($request->integer('per_page', $supplier_type_types->count()));

            return $this->successResponse(
                new SupplierTypeCollection($supplier_type_types),
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
     *     path="/typification/supplier_types/find/{id}",
     *     tags={"Typification › Supplier Types"},
     *     summary="Obtener un tipo de proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de proveedor cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(SupplierTypeFindRequest $request, $id)
    {
        try {
            $supplier_type = SupplierType::findOrFail($id);

            return $this->successResponse(
                new SupplierTypeResource($supplier_type),
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
     *     path="/typification/supplier_types/store",
     *     tags={"Typification › Supplier Types"},
     *     summary="Crear un tipo de proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SupplierTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de proveedor creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.has_person": "Indicador de datos de persona", "settings.has_account_bank": "Indicador de datos de bancarios", "settings.in_production_order": "Indicador de orden de producción", "settings.paragraph": "Indicador de apartado al que pertenece en la orden de producción"},
     *                 "errors": {"settings.paragraph": {"The Indicador de apartado al que pertenece en la orden de producción field is required when Indicador de orden de producción is true."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(SupplierTypeStoreRequest $request)
    {
        try {
            $supplier_type = new SupplierType;
            $supplier_type->name = $request->input('name');
            $supplier_type->description = $request->input('description');
            $supplier_type->settings = $request->input('settings');

            $supplier_type->save();

            return $this->successResponse(
                new SupplierTypeResource($supplier_type),
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
     *     path="/typification/supplier_types/update/{id}",
     *     tags={"Typification › Supplier Types"},
     *     summary="Editar un tipo de proveedor",
     *     description="Reemplaza nombre, descripción y `settings` completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/SupplierTypeRequest",
     *             example={"name": "LAVANDERÍA", "description": "PROVEEDORES DE LAVADO DE PRENDAS", "settings": {"has_person": true, "has_account_bank": true, "in_production_order": false}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de proveedor editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de proveedor no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.has_person": "Indicador de datos de persona", "settings.has_account_bank": "Indicador de datos de bancarios", "settings.in_production_order": "Indicador de orden de producción", "settings.paragraph": "Indicador de apartado al que pertenece en la orden de producción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(SupplierTypeUpdateRequest $request, $id)
    {
        try {
            $supplier_type = SupplierType::findOrFail($id);
            $supplier_type->name = $request->input('name');
            $supplier_type->description = $request->input('description');
            $supplier_type->settings = $request->input('settings');

            $supplier_type->save();

            return $this->successResponse(
                new SupplierTypeResource($supplier_type),
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
     *     path="/typification/supplier_types/delete/{id}",
     *     tags={"Typification › Supplier Types"},
     *     summary="Desactivar un tipo de proveedor",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de proveedor desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(SupplierTypeDeleteRequest $request, $id)
    {
        try {
            $supplier_type = SupplierType::findOrFail($id);
            $supplier_type->delete();

            return $this->successResponse(
                new SupplierTypeResource($supplier_type),
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
     *     path="/typification/supplier_types/restore/{id}",
     *     tags={"Typification › Supplier Types"},
     *     summary="Restaurar un tipo de proveedor desactivado",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de proveedor restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(SupplierTypeRestoreRequest $request, $id)
    {
        try {
            $supplier_type = SupplierType::withTrashed()->findOrFail($id);
            $supplier_type->restore();

            return $this->successResponse(
                new SupplierTypeResource($supplier_type),
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
