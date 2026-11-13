<?php

namespace App\Http\Controllers;

use App\Http\Requests\Management\Collection\CollectionFindRequest;
use App\Http\Requests\Management\Production\ProductionOrderSaveRequest;
use App\Http\Resources\Management\Production\CollectionResource;
use App\Http\Resources\TechnicalSheet\ProductionOrder\ProductionOrderResource;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantities;
use App\Models\TechnicalSheet;
use App\Services\ProductionOrderCurveService;
use App\Services\ProductionPlanService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="ProductionOrderDetailQuantity",
 *     type="object",
 *     description="Cantidad de una talla en un detalle de producto (curva).",
 *
 *     @OA\Property(property="id", type="integer", example=88410),
 *     @OA\Property(property="production_order_detail_id", type="integer", example=40122),
 *     @OA\Property(property="size_id", type="integer", example=101),
 *     @OA\Property(property="quantity", type="integer", example=120),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-10-22T16:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-10-22T16:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="ManagementProductionOrderDetail",
 *     type="object",
 *     description="Detalle de la orden. Según `model_type`: Product = curva de un destino (con cantidades por talla); Supply = tela; Color; Process = proceso programado (`settings.date`); Piece; ProductionOrder = orden de origen de una reasignación de curva.",
 *
 *     @OA\Property(property="id", type="integer", example=40122),
 *     @OA\Property(property="production_order_id", type="integer", example=1312),
 *     @OA\Property(property="model_type", type="string", enum={"App\Models\Product", "App\Models\Supply", "App\Models\Color", "App\Models\Process", "App\Models\Piece", "App\Models\ProductionOrder"}, example="App\Models\Product"),
 *     @OA\Property(property="model_id", type="integer", example=1460),
 *     @OA\Property(property="destination", type="string", nullable=true, enum={"NACIONAL", "MEDELLIN", "STARA"}, description="Solo en detalles Product. NACIONAL y MEDELLIN usan el producto de la ficha; STARA usa el producto de la marca STARA asociado a la ficha (`model_has_product`).", example="NACIONAL"),
 *     @OA\Property(property="settings", nullable=true, description="Datos extra. En detalles Process trae `date` (fecha de programación).", example={"date": "2026-11-03"}),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-10-22T16:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-10-22T16:00:00.000000Z"),
 *     @OA\Property(property="production_order_detail_quantities", type="array", description="Solo en detalles Product.", @OA\Items(ref="#/components/schemas/ProductionOrderDetailQuantity")),
 *     @OA\Property(property="model", type="object", additionalProperties=true, description="El registro al que apunta `model_type`/`model_id` (producto, insumo, proceso, orden…).")
 * )
 *
 * @OA\Schema(
 *     schema="ManagementProductionOrder",
 *     type="object",
 *     description="Orden de producción como viene en `find` (modelo de Eloquent, fechas ISO).",
 *
 *     @OA\Property(property="id", type="integer", example=1312),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="6a1f0c3e-2b7d-4e59-9a14-0c8e5d7f2b31"),
 *     @OA\Property(property="technical_sheet_id", type="integer", example=905),
 *     @OA\Property(property="consecutive", type="string", description="Generado por el procedimiento `generate_production_order_consecutive()` al crear.", example="PO261022001"),
 *     @OA\Property(property="cut", type="string", description="Corte: letra consecutiva por ficha técnica (la primera es A).", example="B"),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "Aprobado", "Cancelado"}, example="Pendiente"),
 *     @OA\Property(property="production_place", type="string", enum={"BLESS", "SATELITE"}, description="BLESS = se confecciona en Bless; SATELITE = proveedor externo.", example="SATELITE"),
 *     @OA\Property(property="supplier_id", type="integer", nullable=true, description="Proveedor (taller satélite). Obligatorio si `production_place` es SATELITE.", example=340),
 *     @OA\Property(property="priority", type="string", description="Nivel de prioridad, de 1 (muy baja) a 5 (muy alta).", example="4"),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(
 *             property="priority_checks",
 *             type="object",
 *             description="Criterios marcados al definir la prioridad (hoy se marcan a mano; ver pendientes).",
 *             @OA\Property(property="current_run", type="boolean", description="Correría actual: la colección de la ficha es la que está en curso.", example=true),
 *             @OA\Property(property="supply", type="boolean", description="Insumo: hay los demás insumos (botones, bordados…), aparte de los rollos.", example=true),
 *             @OA\Property(property="sale", type="boolean", description="Venta: la referencia tiene demanda.", example=true),
 *             @OA\Property(property="rolls", type="boolean", description="Rollos: los rollos asignados a la orden están disponibles.", example=false)
 *         )
 *     ),
 *     @OA\Property(property="efficiency", nullable=true, example=null),
 *     @OA\Property(property="width", nullable=true, example=null),
 *     @OA\Property(property="pocket_fabric", type="boolean", nullable=true, example=null),
 *     @OA\Property(property="observation", type="string", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-10-22T16:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-10-22T16:00:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="production_order_details", type="array", @OA\Items(ref="#/components/schemas/ManagementProductionOrderDetail")),
 *     @OA\Property(property="fabric", nullable=true, description="Detalle de tela (Supply) con su `model`.", allOf={@OA\Schema(ref="#/components/schemas/ManagementProductionOrderDetail")}),
 *     @OA\Property(property="color", type="array", description="Color de la orden (0 o 1 elemento).", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="supplier", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/Subitem")}),
 *     @OA\Property(property="reassignments", type="array", description="Detalles ProductionOrder: órdenes de origen de las que se reasignó curva.", @OA\Items(ref="#/components/schemas/ManagementProductionOrderDetail"))
 * )
 *
 * @OA\Schema(
 *     schema="ManagementProductionTechnicalSheet",
 *     description="Ficha técnica en la gestión de producción (solo las que no están en Pendiente). Igual que en la gestión de colecciones, pero sin `supplies`, con las tallas de la marca (`product.trademark.sizes`), los productos asociados (`products`, por ejemplo el de STARA) y sus órdenes de producción.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/ManagementTechnicalSheet"),
 *         @OA\Schema(
 *
 *             @OA\Property(property="products", type="array", description="Productos asociados a la ficha en `model_has_product` (por ejemplo, el producto de la marca STARA).", @OA\Items(type="object", additionalProperties=true)),
 *             @OA\Property(property="production_orders", type="array", @OA\Items(ref="#/components/schemas/ManagementProductionOrder"))
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="ManagementProductionCollectionResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="collection",
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/Collection"),
 *                 @OA\Schema(@OA\Property(property="technical_sheets", type="array", @OA\Items(ref="#/components/schemas/ManagementProductionTechnicalSheet")))
 *             }
 *         ),
 *
 *         @OA\Property(property="status_production_order", type="array", @OA\Items(type="string"), example={"Pendiente", "Cancelado", "Aprobado"}),
 *         @OA\Property(
 *             property="checks",
 *             type="object",
 *             description="Criterios de prioridad (catálogo fijo).",
 *             additionalProperties=true,
 *             example={"current_run": {"value": "current_run", "label": "Correría actual"}, "supply": {"value": "supply", "label": "Insumo"}, "sale": {"value": "sale", "label": "Venta"}, "rolls": {"value": "rolls", "label": "Rollos"}}
 *         ),
 *         @OA\Property(
 *             property="rules",
 *             type="array",
 *             description="Reglas que convierten los criterios marcados en un nivel (1 = 0 o 1 criterio; 2 = 2 o más, sin Correría actual + Venta juntas; 3 = Correría actual + Venta; 4 = Correría actual + Venta + Insumo o Rollos; 5 = los cuatro).",
 *
 *             @OA\Items(type="object", additionalProperties=true),
 *             example={{"value": 3, "rules": {"required": {"current_run", "sale"}}}, {"value": 5, "rules": {"required": {"current_run", "supply", "sale", "rolls"}}}}
 *         ),
 *
 *         @OA\Property(
 *             property="levels",
 *             type="array",
 *             description="Niveles de prioridad con etiqueta, color de texto y color de fondo.",
 *
 *             @OA\Items(type="object", additionalProperties=true),
 *             example={{"value": 1, "label": "Muy Baja", "color": "#16A34A", "background": "#DCFCE7"}, {"value": 4, "label": "Alta", "color": "#EA580C", "background": "#FFEDD5"}}
 *         )
 *     ),
 *
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ManagementProductionOrderSaveRequest",
 *     type="object",
 *     required={"technical_sheet_id", "cut", "status", "production_place", "priority", "production_order_details"},
 *
 *     @OA\Property(property="id", type="integer", nullable=true, description="Orden a editar. Si no se envía, se crea una nueva con consecutivo y UUID.", example=null),
 *     @OA\Property(property="technical_sheet_id", type="integer", example=905),
 *     @OA\Property(property="cut", type="string", maxLength=2, description="En MAYÚSCULAS. Al crear debe ser el siguiente al último corte de la ficha (el primero es A).", example="B"),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "Aprobado", "Cancelado"}, example="Pendiente"),
 *     @OA\Property(property="production_place", type="string", enum={"BLESS", "SATELITE"}, example="SATELITE"),
 *     @OA\Property(property="supplier_id", type="integer", nullable=true, description="Obligatorio si `production_place` es SATELITE.", example=340),
 *     @OA\Property(property="priority", type="integer", enum={1, 2, 3, 4, 5}, example=4),
 *     @OA\Property(property="color_id", type="integer", nullable=true, description="Color de la orden; debe estar asociado a la tela. No está en las reglas (ver pendientes).", example=377),
 *     @OA\Property(property="settings", type="object", nullable=true, description="Por ejemplo `priority_checks`.", example={"priority_checks": {"current_run": true, "supply": true, "sale": true, "rolls": false}}),
 *     @OA\Property(property="reasigned_curve", type="boolean", description="true si la orden recibe unidades de otras órdenes.", example=false),
 *     @OA\Property(
 *         property="reassigned_origins",
 *         type="array",
 *         description="Obligatorio si `reasigned_curve` es true. Por cada orden de origen: de qué destinos se descuenta (en orden NACIONAL → MEDELLIN → STARA) y las unidades por talla a pasar.",
 *
 *         @OA\Items(
 *             type="object",
 *             required={"production_order_id", "selected_rows", "reassigned_details"},
 *
 *             @OA\Property(property="production_order_id", type="integer", example=1298),
 *             @OA\Property(property="selected_rows", type="array", @OA\Items(type="string", enum={"NACIONAL", "MEDELLIN", "STARA"}), example={"NACIONAL", "MEDELLIN"}),
 *             @OA\Property(property="reassigned_details", type="array", @OA\Items(type="object", @OA\Property(property="sizes", type="array", @OA\Items(type="object", @OA\Property(property="size_id", type="integer", example=101), @OA\Property(property="quantity", type="integer", example=30)))))
 *         )
 *     ),
 *     @OA\Property(
 *         property="production_order_details",
 *         type="array",
 *         minItems=1,
 *         description="Debe incluir al menos una tela (Supply) y al menos un producto (curva de un destino). Basta con un destino; cada destino enviado exige cantidades mayores que 0.",
 *
 *         @OA\Items(
 *             type="object",
 *             required={"model_type", "model_id"},
 *
 *             @OA\Property(property="id", type="integer", nullable=true, description="Detalle existente a actualizar; null para crear.", example=null),
 *             @OA\Property(property="model_type", type="string", enum={"App\Models\Product", "App\Models\Supply", "App\Models\Color", "App\Models\Process", "App\Models\Piece", "App\Models\ProductionOrder"}, example="App\Models\Product"),
 *             @OA\Property(property="model_id", type="integer", example=1460),
 *             @OA\Property(property="destination", type="string", nullable=true, enum={"NACIONAL", "MEDELLIN", "STARA"}, description="Obligatorio en detalles Product.", example="NACIONAL"),
 *             @OA\Property(property="settings", type="object", nullable=true, description="En detalles Process: `date` (Y-m-d). Dos procesos no pueden tener la misma fecha y debe respetarse el orden entre procesos anteriores y posteriores.", example={"date": "2026-11-03"}),
 *             @OA\Property(property="sizes", type="array", description="Solo en detalles Product: cantidades por talla.", @OA\Items(type="object", @OA\Property(property="id", type="integer", nullable=true, example=null), @OA\Property(property="size_id", type="integer", example=101), @OA\Property(property="quantity", type="integer", example=120)))
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ManagementProductionOrderResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="production_order",
 *             type="object",
 *             description="Orden como la entrega `ProductionOrderResource`: fechas `Y-m-d H:i:s`, con `technical_sheet`, `strokes`, `production_order_details`, `fabric`, `fabric_id`, `color`, `pieces`, `rolls` y `reassignments`. No incluye `priority` (ver pendientes).",
 *             additionalProperties=true,
 *             @OA\Property(property="id", type="integer", example=1312),
 *             @OA\Property(property="technical_sheet_id", type="integer", example=905),
 *             @OA\Property(property="consecutive", type="string", example="PO261022001"),
 *             @OA\Property(property="cut", type="string", example="B"),
 *             @OA\Property(property="status", type="string", example="Pendiente"),
 *             @OA\Property(property="production_place", type="string", example="SATELITE"),
 *             @OA\Property(property="supplier_id", type="integer", nullable=true, example=340),
 *             @OA\Property(property="settings", type="object", nullable=true),
 *             @OA\Property(property="fabric_id", type="integer", example=40120),
 *             @OA\Property(property="production_order_details", type="array", @OA\Items(ref="#/components/schemas/ManagementProductionOrderDetail"))
 *         ),
 *         @OA\Property(property="status_production_order", type="array", @OA\Items(type="string"), example={"Pendiente", "Cancelado", "Aprobado"})
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 */
class ManagementProductionController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/management/production/find/{id}",
     *     tags={"Management › Production"},
     *     summary="Obtener una colección con sus fichas y órdenes de producción para programar",
     *     description="Carga la colección con sus fichas técnicas que **no** están en Pendiente, cada una con su producto (marca con sus tallas, subcategoría y categoría), catálogos, fotos, productos asociados (STARA) y órdenes de producción con detalles, cantidades por talla, tela, color, proveedor y reasignaciones. Incluye los catálogos de estados de orden y de prioridad (criterios, reglas y niveles). `ready_references` agrupa por proceso de Gestión los lotes listos para programar: el proceso está En revisión o Aprobado en la ficha y no tiene fecha; después del primero, además tiene un ingreso aprobado al proceso. `unworked_dates` lista las fechas que ya pasaron sin que el lote se trabajara en el proceso (no se borran: la pantalla las muestra en rojo para reprogramar).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(name="search", in="query", required=false, description="Solo las fichas cuyo producto (o producto STARA asociado) contenga este código. `ready_references` no se filtra.", @OA\Schema(type="string", example="BO100000")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Colección cargada correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ManagementProductionCollectionResponse")
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(CollectionFindRequest $request, $id)
    {
        try {
            $plan = app(ProductionPlanService::class);
            // «BO123589-B»: también se busca como referencia y lote
            $lot = preg_match('/^(.+)-([^-]+)$/', (string) $request->input('search'), $parts) ? $parts : null;
            $collection = Collection::with([
                'item',
                'technical_sheets' => function ($query) use ($request, $lot) {
                    $query->where('status', '!=', TechnicalSheet::PENDING)
                        ->when($request->filled('search'), fn ($sheets) => $sheets->where(fn ($code) => $code
                            ->whereHas('product', fn ($product) => $product->where('code', 'like', '%'.$request->input('search').'%'))
                            ->orWhereHas('products', fn ($product) => $product->where('code', 'like', '%'.$request->input('search').'%'))
                            ->when($lot, fn ($by_lot) => $by_lot->orWhere(fn ($sheet) => $sheet
                                ->whereHas('product', fn ($product) => $product->where('code', 'like', '%'.$lot[1].'%'))
                                ->whereHas('production_orders', fn ($order) => $order->where('cut', $lot[2]))))))
                        ->with(['technical_sheet_details' => ['typology'], 'product' => ['trademark' => ['sizes'], 'subcategory' => ['category']], 'garment_type', 'wash_tone', 'boot_type', 'photo_d', 'photo_t', 'products', 'production_orders' => ['production_order_details' => ['production_order_detail_quantities', 'model'], 'fabric' => ['model'], 'color', 'supplier', 'reassignments']]);
                },
            ])->findOrFail($id);
            $collection->setAttribute('unworked_dates', $plan->unworkedDates((int) $id));
            $collection->setAttribute('ready_references', $plan->readyReferences((int) $id));
            $collection->setAttribute('worked_processes', $plan->workedProcesses((int) $id));

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
     *     path="/management/production/save",
     *     tags={"Management › Production"},
     *     summary="Crear o actualizar una orden de producción",
     *     description="Guarda una orden completa: datos generales (corte, estado, lugar de producción, proveedor, prioridad), color, detalles (tela, curva por destino con cantidades por talla, procesos con fecha de programación…) y, si `reasigned_curve` es true, descuenta de las órdenes de origen las unidades reasignadas (por talla, recorriendo los destinos elegidos en orden NACIONAL → MEDELLIN → STARA). Si es nueva, le asigna consecutivo y UUID. El frontend envía una petición por cada orden modificada. ⚠️ Con tela y color, la validación del color hoy puede fallar con 500 (ver pendientes).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ManagementProductionOrderSaveRequest",
     *             example={
     *                 "id": null, "technical_sheet_id": 905, "cut": "B", "status": "Pendiente", "production_place": "SATELITE", "supplier_id": 340, "priority": 4, "color_id": 377,
     *                 "settings": {"priority_checks": {"current_run": true, "supply": true, "sale": true, "rolls": false}},
     *                 "reasigned_curve": false,
     *                 "production_order_details": {
     *                     {"id": null, "model_type": "App\Models\Supply", "model_id": 7020, "destination": null},
     *                     {"id": null, "model_type": "App\Models\Product", "model_id": 1460, "destination": "NACIONAL", "sizes": {{"id": null, "size_id": 101, "quantity": 120}, {"id": null, "size_id": 102, "quantity": 180}}},
     *                     {"id": null, "model_type": "App\Models\Product", "model_id": 1475, "destination": "STARA", "sizes": {{"id": null, "size_id": 101, "quantity": 40}, {"id": null, "size_id": 102, "quantity": 60}}},
     *                     {"id": null, "model_type": "App\Models\Process", "model_id": 24, "destination": null, "settings": {"date": "2026-11-03"}}
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Orden de producción guardada correctamente (también al crearla: responde 200, no 201).",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ManagementProductionOrderResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos: corte no consecutivo, falta tela o curva, destino sin curva, color no asociado a la tela, fechas de procesos repetidas o fuera de orden, proveedor faltante en SATELITE, etc.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"technical_sheet_id": "Identificador de la ficha técnica", "cut": "Identificador de lote", "fabric_id": "Identificador de la tela", "status": "Estado de la orden de producción"},
     *                 "errors": {"cut": {"No es consecutivo."}, "fabric_id": {"Es obligatorio."}, "production_order_details.24.settings.date": {"La fecha de programación de este proceso ya está asignada a otro proceso."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor. Este endpoint no captura excepciones: responde con el formato por defecto de Laravel, y lo guardado antes del fallo (incluido el descuento en las órdenes de origen) no se revierte (ver pendientes).",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Server Error"))
     *     )
     * )
     */
    public function save(ProductionOrderSaveRequest $request)
    {
        try {
            $production_order = ProductionOrder::firstOrNew(['id' => $request->input('id')]);

            if (! $production_order->exists) {
                $result = DB::select('CALL generate_production_order_consecutive()');
                $production_order->consecutive = $result[0]->consecutive;
                $production_order->uuid = Str::uuid();
            }

            $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
            $production_order->cut = $request->input('cut');
            $production_order->status = $request->input('status');
            $production_order->production_place = $request->input('production_place');
            $production_order->priority = $request->input('priority');
            $production_order->settings = $request->input('settings');

            if ($request->input('supplier_id')) {
                $production_order->supplier_id = $request->integer('supplier_id');
            }

            $production_order->save();

            $production_order->color()->sync([$request->integer('color_id')]);

            $this->save_details($request->input('production_order_details', []), $production_order->id);

            // El descuento en las órdenes de origen va al final: si algo falla antes, no se pierden unidades
            if ($request->boolean('reasigned_curve')) {
                app(ProductionOrderCurveService::class)->reassign($request->input('reassigned_origins', []), $production_order->id);
            }

            $production_order->load(['production_order_details' => ['model', 'production_order_detail_quantities']]);

            return $this->successResponse(
                new ProductionOrderResource($production_order),
                $this->getMessage('Success'),
                $production_order->wasRecentlyCreated ? 201 : 200
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

    private function save_details(array $production_order_details, int $production_order_id)
    {
        try {
            foreach ($production_order_details as $production_order_detail_item) {
                $quantities = [];

                if ($production_order_detail_item['model_type'] === Product::class) {
                    $quantities = $production_order_detail_item['sizes'];

                    if (array_sum(array_column($quantities, 'quantity')) === 0) {
                        continue;
                    }
                }

                if ($production_order_detail_item['id']) {
                    $production_order_detail = ProductionOrderDetail::findOrFail($production_order_detail_item['id']);

                    $production_order_detail->update([
                        'model_id' => $production_order_detail_item['model_id'],
                        'model_type' => $production_order_detail_item['model_type'],
                        'destination' => $production_order_detail_item['destination'],
                        'settings' => $production_order_detail_item['settings'] ?? [],
                    ]);
                } else {
                    $production_order_detail = ProductionOrderDetail::create([
                        'production_order_id' => $production_order_id,
                        'model_id' => $production_order_detail_item['model_id'],
                        'model_type' => $production_order_detail_item['model_type'],
                        'destination' => $production_order_detail_item['destination'],
                        'settings' => $production_order_detail_item['settings'] ?? [],
                    ]);
                }

                if ($production_order_detail['model_type'] === Product::class) {
                    foreach ($production_order_detail_item['sizes'] as $size) {
                        ProductionOrderDetailQuantities::updateOrCreate(
                            [
                                'production_order_detail_id' => $production_order_detail->id,
                                'size_id' => $size['size_id'],
                            ],
                            [
                                'quantity' => $size['quantity'],
                            ]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
