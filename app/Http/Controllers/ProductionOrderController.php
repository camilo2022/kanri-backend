<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderAllRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderExportPDFRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderFindRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderStoreRequest;
use App\Http\Requests\TechnicalSheet\ProductionOrder\ProductionOrderUpdateRequest;
use App\Http\Resources\TechnicalSheet\ProductionOrder\ProductionOrderCollection;
use App\Http\Resources\TechnicalSheet\ProductionOrder\ProductionOrderResource;
use App\Models\File;
use App\Models\Piece;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantities;
use App\Models\Supply;
use App\Services\ChromePdfService;
use App\Services\FileService;
use App\Services\ProductionOrderCurveService;
use App\Services\QrCodeService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

/**
 * @OA\Schema(
 *     schema="ProductionOrder",
 *     type="object",
 *     description="Orden de producción (lote: referencia + corte, por ejemplo LM4521-A) como la entrega `ProductionOrderResource` (find, store y update). Fechas en `Y-m-d H:i:s`. Las relaciones se cargan según la operación: `find` carga todas; `store` y `update` las leen al serializar. Este Resource no trae `uuid` (sí lo trae la lista).",
 *     @OA\Property(property="id", type="integer", example=1315),
 *     @OA\Property(property="technical_sheet_id", type="integer", example=905),
 *     @OA\Property(property="consecutive", type="string", description="Consecutivo generado por el procedimiento `generate_production_order_consecutive`.", example="OP-000412"),
 *     @OA\Property(property="efficiency", type="number", example=85),
 *     @OA\Property(property="cut", type="string", description="Identificador del lote (corte) dentro de la referencia.", example="A"),
 *     @OA\Property(property="width", type="number", description="Ancho de la tela.", example=1.6),
 *     @OA\Property(property="pocket_fabric", type="boolean", example=false),
 *     @OA\Property(property="observation", type="string", nullable=true, example="CORTAR CON TENDIDO DOBLE"),
 *     @OA\Property(property="settings", type="array", description="Tendidos de la orden. Cada elemento trae `large` y `quantity` (ver `ProductionOrderRequest`).", @OA\Items(type="object", @OA\Property(property="large", type="number", example=12.5), @OA\Property(property="quantity", type="integer", example=30))),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "Aprobado", "Cancelado"}, example="Pendiente"),
 *     @OA\Property(property="priority", type="integer", nullable=true, description="Prioridad (1 muy baja a 5 muy alta); la asigna Gestión de producción.", example=3),
 *     @OA\Property(property="production_place", type="string", enum={"BLESS", "SATELITE"}, example="BLESS"),
 *     @OA\Property(property="supplier_id", type="integer", nullable=true, description="Proveedor; obligatorio cuando el lugar es SATELITE.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-10-22 11:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-10-22 11:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="technical_sheet", type="object", nullable=true, additionalProperties=true, description="Ficha técnica. En `find` trae el producto (con marca, grupo y tallas, subcategoría y categoría), los productos STARA, el tipo de prenda, el subgrupo, la colección y las fotos."),
 *     @OA\Property(property="strokes", nullable=true, description="Archivo PDF de los trazos (tipo de archivo 3, subtipo 15).", allOf={@OA\Schema(ref="#/components/schemas/File")}),
 *     @OA\Property(property="production_order_details", type="array", description="Detalles de la orden: curva de cada destino (Product, con `production_order_detail_quantities`), tela y rollos (Supply), color, piezas, procesos programados y reasignaciones; `model` es el elemento al que apunta cada uno.", @OA\Items(type="object", additionalProperties=true)),
 *     @OA\Property(property="fabric", type="object", nullable=true, additionalProperties=true, description="Detalle de la tela de la orden (con su `model`)."),
 *     @OA\Property(property="fabric_id", type="integer", nullable=true, description="Identificador del detalle de tela (no del insumo).", example=40118),
 *     @OA\Property(property="color", type="array", description="Color de la orden.", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="pieces", type="array", description="Detalles de tipo pieza.", @OA\Items(type="object", additionalProperties=true)),
 *     @OA\Property(property="rolls", type="array", description="Rollos de tela con metros asignados a la orden.", @OA\Items(type="object", additionalProperties=true)),
 *     @OA\Property(property="reassignments", type="array", description="Reasignaciones de curva desde otras órdenes.", @OA\Items(type="object", additionalProperties=true))
 * )
 *
 * @OA\Schema(
 *     schema="ProductionOrderListItem",
 *     type="object",
 *     description="Orden de producción como la entrega `ProductionOrderCollection` (all). Trae `uuid` y los proveedores, pero no `color`, `rolls`, `reassignments` ni `priority`. Las claves `estimate`, `previous_missing` y `previous` solo salen cuando se envían `process_id`, `model_type` y `model_id` (programación del turno); `planned_date` cuando se envía `process_id` con puesto.",
 *     @OA\Property(property="id", type="integer", example=1315),
 *     @OA\Property(property="has_audits", type="boolean", description="Si la orden tiene historial de auditoría.", example=true),
 *     @OA\Property(property="uuid", type="string", format="uuid", nullable=true, description="Identificador público; se usa en las rutas de PDF.", example="9b2f3a54-6c1e-4d57-8a0f-1e2d3c4b5a69"),
 *     @OA\Property(property="technical_sheet_id", type="integer", example=905),
 *     @OA\Property(property="production_place", type="string", enum={"BLESS", "SATELITE"}, example="BLESS"),
 *     @OA\Property(property="supplier_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="consecutive", type="string", example="OP-000412"),
 *     @OA\Property(property="efficiency", type="number", example=85),
 *     @OA\Property(property="cut", type="string", example="A"),
 *     @OA\Property(property="width", type="number", example=1.6),
 *     @OA\Property(property="pocket_fabric", type="boolean", example=false),
 *     @OA\Property(property="observation", type="string", nullable=true, example="CORTAR CON TENDIDO DOBLE"),
 *     @OA\Property(property="settings", type="array", @OA\Items(type="object", @OA\Property(property="large", type="number", example=12.5), @OA\Property(property="quantity", type="integer", example=30))),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "Aprobado", "Cancelado"}, example="Pendiente"),
 *     @OA\Property(property="created_at", type="string", example="2026-10-22 11:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-10-22 11:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="strokes", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/File")}),
 *     @OA\Property(property="technical_sheet", type="object", nullable=true, additionalProperties=true, description="Ficha técnica con su producto, productos STARA y tipo de prenda (y las tipologías de cada proceso con `with_typologies`)."),
 *     @OA\Property(property="production_order_details", type="array", @OA\Items(type="object", additionalProperties=true)),
 *     @OA\Property(property="fabric", type="object", nullable=true, additionalProperties=true),
 *     @OA\Property(property="pieces", type="array", @OA\Items(type="object", additionalProperties=true)),
 *     @OA\Property(property="supplier", type="object", nullable=true, additionalProperties=true),
 *     @OA\Property(property="estimate", type="object", additionalProperties=true, description="Solo con `process_id`, `model_type` y `model_id`: estimado del lote en el puesto."),
 *     @OA\Property(property="previous_missing", type="array", description="Solo con puesto: puestos anteriores de la cadena donde al lote aún le falta estar programado.", @OA\Items(type="object", @OA\Property(property="model_type", type="string", example="App\Models\Operation"), @OA\Property(property="model_id", type="integer", example=312), @OA\Property(property="name", type="string", example="EXTENDIDO"))),
 *     @OA\Property(property="previous", type="array", description="Solo con puesto: puestos que van antes en la cadena del lote.", @OA\Items(type="object", @OA\Property(property="model_type", type="string", example="App\Models\Operation"), @OA\Property(property="model_id", type="integer", example=312))),
 *     @OA\Property(property="planned_date", type="string", nullable=true, description="Solo con `process_id`: fecha que Gestión de producción le puso al lote en ese proceso.", example="2026-10-27")
 * )
 *
 * @OA\Schema(
 *     schema="ProductionOrderResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="production_order", ref="#/components/schemas/ProductionOrder"),
 *         @OA\Property(property="status_production_order", type="array", description="Estados posibles de una orden.", @OA\Items(type="string"), example={"Pendiente", "Cancelado", "Aprobado"})
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProductionOrderListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="production_orders", type="array", @OA\Items(ref="#/components/schemas/ProductionOrderListItem")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProductionOrderFormDetail",
 *     type="object",
 *     required={"model_type"},
 *     description="Detalle que se guarda con la orden. Según `model_type`: Product = curva de un destino (`destination` y `sizes`); Supply con `rows` = tela distribuida por tallas; Supply sin `rows` = rollo (`settings.quantity` en metros); Piece = pieza (`settings.quantity`); Color = color de la orden. Los productos con curva en 0 y las telas, rollos y piezas con cantidad vacía o 0 no se guardan.",
 *     @OA\Property(property="model_type", type="string", enum={"App\Models\Product", "App\Models\Supply", "App\Models\Color", "App\Models\Piece"}, example="App\Models\Product"),
 *     @OA\Property(property="model_id", type="integer", description="Identificador del producto, insumo, color o pieza. Debe existir; para la tela, el de `fabric_id`; para el color, el de `color_id`.", example=1520),
 *     @OA\Property(property="destination", type="string", nullable=true, enum={"NACIONAL", "MEDELLIN", "STARA"}, description="Obligatorio para los productos.", example="NACIONAL"),
 *     @OA\Property(property="sizes", type="array", description="Curva de un producto: cantidad por talla.", @OA\Items(type="object", @OA\Property(property="size_id", type="integer", example=101), @OA\Property(property="quantity", type="integer", example=120))),
 *     @OA\Property(property="rows", type="array", description="Tela: filas con la cantidad por talla. En cada talla, `id` es el de la cantidad existente (`null` si es nueva).", @OA\Items(type="object", @OA\Property(property="sizes", type="array", @OA\Items(type="object", @OA\Property(property="id", type="integer", nullable=true, example=null), @OA\Property(property="size_id", type="integer", example=101), @OA\Property(property="quantity", type="integer", example=60))))),
 *     @OA\Property(property="settings", type="object", description="Rollo o pieza: `quantity` es obligatoria. En un rollo no puede superar los metros disponibles del rollo.", @OA\Property(property="quantity", type="number", example=45.5))
 * )
 *
 * @OA\Schema(
 *     schema="ProductionOrderRequest",
 *     type="object",
 *     required={"technical_sheet_id", "efficiency", "cut", "fabric_id", "color_id", "width", "pocket_fabric", "status", "production_place", "production_order_details", "settings"},
 *     description="Se envía como `multipart/form-data` para poder adjuntar el PDF de trazos; los arreglos van con la notación de corchetes (`settings[0][large]`). Hay diferencias entre crear y editar (ver cada operación).",
 *     @OA\Property(property="technical_sheet_id", type="integer", description="Ficha técnica de la orden. Debe existir.", example=905),
 *     @OA\Property(property="efficiency", type="number", minimum=1, example=85),
 *     @OA\Property(property="cut", type="string", maxLength=2, description="Corte del lote, en MAYÚSCULAS. No puede repetirse dentro de la misma ficha.", example="A"),
 *     @OA\Property(property="fabric_id", type="integer", description="Tela (insumo) de un tipo de insumo apto para órdenes. Solo se valida: la tela se guarda desde `production_order_details` (insumo con `rows`).", example=5230),
 *     @OA\Property(property="color_id", type="integer", description="Color de la orden. Debe estar asociado a la tela.", example=3120),
 *     @OA\Property(property="width", type="number", minimum=1, maximum=999999.99, description="Ancho de la tela, hasta 2 decimales.", example=1.6),
 *     @OA\Property(property="pocket_fabric", type="boolean", example=false),
 *     @OA\Property(property="observation", type="string", nullable=true, maxLength=1000, example="CORTAR CON TENDIDO DOBLE"),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "Aprobado", "Cancelado"}, example="Pendiente"),
 *     @OA\Property(property="production_place", type="string", enum={"BLESS", "SATELITE"}, example="BLESS"),
 *     @OA\Property(property="supplier_id", type="integer", nullable=true, description="Obligatorio si `production_place` es SATELITE. Debe existir como proveedor.", example=null),
 *     @OA\Property(property="settings", type="array", description="Tendidos del lote. `quantity` no puede repetirse entre elementos.", @OA\Items(type="object", required={"large", "quantity"}, @OA\Property(property="large", type="number", example=12.5), @OA\Property(property="quantity", type="integer", minimum=1, example=30))),
 *     @OA\Property(property="production_order_details", type="array", @OA\Items(ref="#/components/schemas/ProductionOrderFormDetail")),
 *     @OA\Property(property="reasigned_curve", type="boolean", description="`true`: reasigna curva de otras órdenes (requiere `reassigned_origins`). El nombre del campo se escribe así en el código.", example=false),
 *     @OA\Property(
 *         property="reassigned_origins",
 *         type="array",
 *         description="Obligatorio si `reasigned_curve` es `true`. No se permite reasignar de un lote que ya se empezó a trabajar o está programado.",
 *         @OA\Items(
 *             type="object",
 *             required={"production_order_id", "selected_rows", "reassigned_details"},
 *             @OA\Property(property="production_order_id", type="integer", description="Orden de origen.", example=1290),
 *             @OA\Property(property="selected_rows", type="array", @OA\Items(type="string", enum={"NACIONAL", "MEDELLIN", "STARA"})),
 *             @OA\Property(property="reassigned_details", type="array", description="Detalles de la orden de origen que se reasignan (no se valida su contenido).", @OA\Items(type="object", additionalProperties=true))
 *         )
 *     ),
 *     @OA\Property(property="strokes_file", type="object", description="Trazos en PDF (opcional).", @OA\Property(property="file", type="string", format="binary", description="PDF de máximo 10 MB. Al crear, el nombre del archivo (sin extensión) debe ser el código de la referencia seguido de un guion y el corte, por ejemplo `LM4521-A`."), @OA\Property(property="photo_type_id", type="integer", description="Tipo de archivo (no se valida). Hoy: 3.", example=3), @OA\Property(property="photo_subtype_id", type="integer", description="Subtipo de archivo (no se valida). Hoy: 15 = trazos.", example=15))
 * )
 */
class ProductionOrderController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/technical_sheets/production_orders/all",
     *     tags={"Technical Sheets › Production Orders"},
     *     summary="Listar las órdenes de producción",
     *     description="Lista paginada de órdenes (lotes) de todas las fichas, con ficha, producto, curva, tela, piezas, trazos y proveedor. Sin ficha técnica en la ruta exige `start_date` y `end_date` (o `process_id`). Con la ficha en la ruta (`/all/{technical_sheet_id}`) solo las de esa ficha. La usan el cronograma, Gestión de producción y la programación del turno. Sin `per_page` devuelve todas en una sola página.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Texto a buscar en el consecutivo y en el corte.",
     *         @OA\Schema(type="string", example="OP-0004")
     *     ),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "consecutive", "cut", "status", "created_at", "updated_at", "deleted_at"}, example="consecutive")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(ref="#/components/parameters/WithTrashedOptional"),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         description="Fecha inicial (`Y-m-d`). Solo trae órdenes con algún proceso programado desde esa fecha (`settings.date` de un detalle). Obligatoria si no se envía ni la ficha técnica ni `process_id`.",
     *         @OA\Schema(type="string", format="date", example="2026-10-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         description="Fecha final (`Y-m-d`), igual o posterior a `start_date`. Solo trae órdenes con algún proceso programado hasta esa fecha. Obligatoria en los mismos casos que `start_date`.",
     *         @OA\Schema(type="string", format="date", example="2026-10-31")
     *     ),
     *     @OA\Parameter(
     *         name="processes_id[]",
     *         in="query",
     *         required=false,
     *         description="Procesos (al menos uno): solo órdenes con un detalle de alguno de esos procesos; junto con las fechas, programado en ellos.",
     *         @OA\Schema(type="array", @OA\Items(type="integer", example=301))
     *     ),
     *     @OA\Parameter(
     *         name="has_programations",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: solo órdenes con algún proceso programado; `false`/`0`: solo las que no tienen ninguno. No pasa por validación (el código lo lee directamente).",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="with_typologies",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: cada ficha trae además las tipologías de sus detalles de proceso (cronograma de producción).",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="process_id",
     *         in="query",
     *         required=false,
     *         description="Proceso a programar. Solo trae los lotes programables en ese proceso (la ficha lo tiene en revisión o aprobado y, si no es el primero, ya recibió unidades). Con `model_type` y `model_id` filtra por puesto y agrega `estimate`, `previous_missing` y `previous`.",
     *         @OA\Schema(type="integer", example=301)
     *     ),
     *     @OA\Parameter(
     *         name="model_type",
     *         in="query",
     *         required=false,
     *         description="Tipo de puesto de trabajo. Se envía junto con `model_id`.",
     *         @OA\Schema(type="string", enum={"App\Models\Process", "App\Models\Subprocess", "App\Models\Operation"}, example="App\Models\Operation")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         required=false,
     *         description="Puesto de trabajo (proceso, subproceso u operación activo). Se envía junto con `model_type`.",
     *         @OA\Schema(type="integer", example=312)
     *     ),
     *     @OA\Parameter(
     *         name="pending_previous",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: con puesto, incluye también los lotes a los que solo les falta el puesto anterior de la cadena (`previous_missing` dice cuál).",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="productivity_id",
     *         in="query",
     *         required=false,
     *         description="Turno de productividad cuyas filas sin iniciar no cuentan en `previous_missing`.",
     *         @OA\Schema(type="integer", example=88)
     *     ),
     *     @OA\Parameter(
     *         name="chain",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: con `process_id`, programación en cadena (CORTE): los lotes a los que les falta cualquier puesto de la cadena, no solo el indicado.",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de órdenes de producción cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductionOrderListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros inválidos, ficha técnica inexistente o faltan las fechas.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"start_date": "start date", "end_date": "end date"}, "errors": {"start_date": {"El campo start date es obligatorio cuando none de technical sheet id / process id están presentes."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/technical_sheets/production_orders/all/{technical_sheet_id}",
     *     tags={"Technical Sheets › Production Orders"},
     *     summary="Listar las órdenes de producción de una ficha técnica",
     *     description="Igual que `/all`, pero solo con las órdenes de la ficha de la ruta. Con la ficha, `start_date` y `end_date` dejan de ser obligatorias. Sin `per_page` devuelve todas en una sola página.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="technical_sheet_id",
     *         in="path",
     *         required=true,
     *         description="Ficha técnica. Debe existir.",
     *         @OA\Schema(type="integer", example=905)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Texto a buscar en el consecutivo y en el corte.",
     *         @OA\Schema(type="string", example="OP-0004")
     *     ),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "consecutive", "cut", "status", "created_at", "updated_at", "deleted_at"}, example="consecutive")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(ref="#/components/parameters/WithTrashedOptional"),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         description="Fecha inicial (`Y-m-d`). Solo trae órdenes con algún proceso programado desde esa fecha (`settings.date` de un detalle). Obligatoria si no se envía ni la ficha técnica ni `process_id`.",
     *         @OA\Schema(type="string", format="date", example="2026-10-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         description="Fecha final (`Y-m-d`), igual o posterior a `start_date`. Solo trae órdenes con algún proceso programado hasta esa fecha. Obligatoria en los mismos casos que `start_date`.",
     *         @OA\Schema(type="string", format="date", example="2026-10-31")
     *     ),
     *     @OA\Parameter(
     *         name="processes_id[]",
     *         in="query",
     *         required=false,
     *         description="Procesos (al menos uno): solo órdenes con un detalle de alguno de esos procesos; junto con las fechas, programado en ellos.",
     *         @OA\Schema(type="array", @OA\Items(type="integer", example=301))
     *     ),
     *     @OA\Parameter(
     *         name="has_programations",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: solo órdenes con algún proceso programado; `false`/`0`: solo las que no tienen ninguno. No pasa por validación (el código lo lee directamente).",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="with_typologies",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: cada ficha trae además las tipologías de sus detalles de proceso (cronograma de producción).",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="process_id",
     *         in="query",
     *         required=false,
     *         description="Proceso a programar. Solo trae los lotes programables en ese proceso (la ficha lo tiene en revisión o aprobado y, si no es el primero, ya recibió unidades). Con `model_type` y `model_id` filtra por puesto y agrega `estimate`, `previous_missing` y `previous`.",
     *         @OA\Schema(type="integer", example=301)
     *     ),
     *     @OA\Parameter(
     *         name="model_type",
     *         in="query",
     *         required=false,
     *         description="Tipo de puesto de trabajo. Se envía junto con `model_id`.",
     *         @OA\Schema(type="string", enum={"App\Models\Process", "App\Models\Subprocess", "App\Models\Operation"}, example="App\Models\Operation")
     *     ),
     *     @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         required=false,
     *         description="Puesto de trabajo (proceso, subproceso u operación activo). Se envía junto con `model_type`.",
     *         @OA\Schema(type="integer", example=312)
     *     ),
     *     @OA\Parameter(
     *         name="pending_previous",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: con puesto, incluye también los lotes a los que solo les falta el puesto anterior de la cadena (`previous_missing` dice cuál).",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="productivity_id",
     *         in="query",
     *         required=false,
     *         description="Turno de productividad cuyas filas sin iniciar no cuentan en `previous_missing`.",
     *         @OA\Schema(type="integer", example=88)
     *     ),
     *     @OA\Parameter(
     *         name="chain",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: con `process_id`, programación en cadena (CORTE): los lotes a los que les falta cualquier puesto de la cadena, no solo el indicado.",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de órdenes de producción cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductionOrderListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros inválidos, ficha técnica inexistente o faltan las fechas.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"start_date": "start date", "end_date": "end date"}, "errors": {"start_date": {"El campo start date es obligatorio cuando none de technical sheet id / process id están presentes."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(ProductionOrderAllRequest $request, $technical_sheet_id = null)
    {
        try {
            $production_orders = ProductionOrder::with(['strokes', 'supplier', 'fabric', 'pieces', 'technical_sheet' => ['product', 'products', 'garment_type'], 'production_order_details' => ['production_order_detail_quantities', 'model']])
                ->when($technical_sheet_id, function ($query) use ($technical_sheet_id) {
                    return $query->where('technical_sheet_id', $technical_sheet_id);
                })
                ->when($request->has('has_programations'), function ($query) use ($request) {
                    if ($request->boolean('has_programations')) {
                        return $query->whereHas('production_order_details', function ($r) {
                            $r->whereNotNull('settings->date');
                        });
                    }

                    return $query->whereDoesntHave('production_order_details', function ($r) {
                        $r->whereNotNull('settings->date');
                    });
                })
                ->when($request->filled('processes_id') || $request->filled('start_date') || $request->filled('end_date'),
                    function ($query) use ($request) {
                        return $query->whereHas('production_order_details', function ($r) use ($request) {
                            if ($request->filled('processes_id')) {
                                $r->whereIn('model_id', $request->input('processes_id'));
                            }

                            if ($request->filled('start_date')) {
                                $r->whereNotNull('settings->date')
                                    ->where('settings->date', '>=', $request->date('start_date')->format('Y-m-d'));
                            }

                            if ($request->filled('end_date')) {
                                $r->whereNotNull('settings->date')
                                    ->where('settings->date', '<=', $request->date('end_date')->format('Y-m-d'));
                            }
                        });
                    }
                )
                ->when($request->boolean('with_typologies'), function ($query) {
                    return $query->with(['technical_sheet' => ['technical_sheet_details_processes' => ['typology']]]);
                })
                ->when($request->filled('process_id'), function ($query) use ($request) {
                    $workstation = $request->filled('model_type') ? $request->input('model_type')::find($request->integer('model_id')) : null;

                    return $request->boolean('chain')
                        ? $query->chainPending($request->integer('process_id'))
                        : $query->programmableIn($request->integer('process_id'), $workstation, $request->boolean('pending_previous'));
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $production_orders = $production_orders->paginate($request->integer('per_page', $production_orders->count()));

            return $this->successResponse(
                new ProductionOrderCollection($production_orders),
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
     *     path="/technical_sheets/production_orders/find/{id}",
     *     tags={"Technical Sheets › Production Orders"},
     *     summary="Consultar una orden de producción",
     *     description="Devuelve la orden con su ficha (producto con marca, grupo y tallas, subcategoría y categoría, tipo de prenda, subgrupo, colección y fotos), detalles con curvas, tela, color, piezas, rollos (con su tipo de insumo y los datos configurables del tipo), reasignaciones y derivados. Consulta también órdenes desactivadas.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Orden de producción cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductionOrderResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="La orden de producción no existe.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"id": "Identificador de la orden de producción"}, "errors": {"id": {"No hay ningún registro."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(ProductionOrderFindRequest $request, $id)
    {
        try {
            $production_order = ProductionOrder::with(['technical_sheet' => ['product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']], 'products', 'garment_type', 'subgroup', 'collection', 'photo_d', 'photo_t'], 'production_order_details' => ['production_order_detail_quantities', 'model'], 'fabric' => ['model'], 'pieces', 'rolls' => ['model' => ['supply_type', 'production_order_details']], 'reassignments' => ['model' => ['technical_sheet' => ['product']]], 'derivatives' => ['model' => ['technical_sheet' => ['product']]]])
                ->findOrFail($id);

            return $this->successResponse(
                new ProductionOrderResource($production_order),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/technical_sheets/production_orders/store",
     *     tags={"Technical Sheets › Production Orders"},
     *     summary="Crear una orden de producción",
     *     description="Crea el lote de una ficha técnica con un consecutivo nuevo y un `uuid`. Guarda el color, los detalles (curvas, tela, rollos y piezas) y, si se envía, el PDF de trazos; también puede reasignar curva de otras órdenes (`reasigned_curve`). El PDF de trazos es opcional; si el nombre no es el código de la referencia seguido del corte (`LM4521-A`), se rechaza. Los productos exigen destino y curva en alguna talla; los rollos no pueden superar los metros disponibles. Responde la orden creada.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/ProductionOrderRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Orden de producción creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductionOrderResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos, corte repetido en la ficha, rollo sin metros suficientes o reasignación de un lote ya empezado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"cut": "Identificador de lote", "efficiency": "Eficiencia"}, "errors": {"cut": {"Ya existe un lote con ese corte para esta referencia."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(ProductionOrderStoreRequest $request)
    {
        try {
            $result = DB::select('CALL generate_production_order_consecutive()');
            $production_order = new ProductionOrder;
            $consecutive = $result[0]->consecutive;

            $production_order->consecutive = $consecutive;
            $production_order->uuid = Str::uuid();
            $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
            $production_order->efficiency = $request->input('efficiency');
            $production_order->cut = $request->input('cut');
            $production_order->width = $request->input('width');
            $production_order->pocket_fabric = $request->boolean('pocket_fabric');
            $production_order->observation = $request->input('observation');
            $production_order->status = $request->input('status');
            $production_order->settings = $request->input('settings');
            $production_order->production_place = $request->input('production_place');

            if ($request->input('supplier_id')) {
                $production_order->supplier_id = $request->integer('supplier_id');
            }

            $production_order->save();

            if ($request->filled('strokes_file.strokes_file_id')) {

                $file = File::findOrFail($request->input('strokes_file.strokes_file_id'));

                File::create([
                    'model_id' => $production_order->id,
                    'model_type' => ProductionOrder::class,
                    'file_type_id' => $file->file_type_id,
                    'file_subtype_id' => $file->file_subtype_id,
                    'name' => $file->name,
                    'path' => $file->path,
                    'mime' => $file->mime,
                    'extension' => $file->extension,
                    'size' => $file->size,
                    'metadata' => $file->metadata,
                ]);
            }

            if ($request->hasFile('strokes_file')) {
                app(FileService::class)->save(
                    $production_order,
                    $request->file('strokes_file.file'),
                    $request->integer('strokes_file.photo_type_id'),
                    $request->integer('strokes_file.photo_subtype_id'),
                    'public',
                    'production_order'
                );
            }

            $production_order->color()->sync([$request->input('color_id')]);

            if ($request->boolean('reasigned_curve')) {
                app(ProductionOrderCurveService::class)->reassign($request->input('reassigned_origins', []), $production_order->id);
            }

            $this->save_details($request->input('production_order_details', []), $production_order->id);

            return $this->successResponse(
                new ProductionOrderResource($production_order),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/technical_sheets/production_orders/update/{id}",
     *     tags={"Technical Sheets › Production Orders"},
     *     summary="Editar una orden de producción",
     *     description="Para adjuntar el PDF de trazos (multipart) el frontend envía **POST** a esta misma URL con el campo `_method=PUT`, porque PHP no lee archivos en un PUT multipart. Actualiza los datos de la orden, el color, los detalles y el trazo (se agrega como documento nuevo). Lo que la edición quita (destino con curva en 0, piezas o rollos que ya no llegan) se pone en 0 o se elimina; en un lote que ya se empezó a trabajar o está programado no se permite quitarlos. Al editar no se acepta `strokes_file.strokes_file_id` y el nombre del PDF no se compara con el código y el corte. Responde la orden editada.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/ProductionOrderRequest"),
     *                     @OA\Schema(@OA\Property(property="_method", type="string", enum={"PUT"}, description="Solo si se envía como POST."))
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orden de producción editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductionOrderResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos, orden no registrada, corte repetido en la ficha o intento de quitar destinos, piezas o rollos de un lote ya empezado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"id": "Identificador de la orden de producción"}, "errors": {"pieces": {"No se pueden quitar piezas: el lote ya se empezó a trabajar."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(ProductionOrderUpdateRequest $request, $id)
    {
        try {
            $production_order = ProductionOrder::findOrFail($id);

            $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
            $production_order->efficiency = $request->input('efficiency');
            $production_order->cut = $request->input('cut');
            $production_order->width = $request->input('width');
            $production_order->pocket_fabric = $request->boolean('pocket_fabric');
            $production_order->observation = $request->input('observation');
            $production_order->status = $request->input('status');
            $production_order->settings = $request->input('settings');
            $production_order->production_place = $request->input('production_place');

            if ($request->input('supplier_id')) {
                $production_order->supplier_id = $request->integer('supplier_id');
            }

            $production_order->save();

            if ($request->hasFile('strokes_file')) {
                app(FileService::class)->save_document(
                    $production_order,
                    $request->file('strokes_file.file'),
                    $request->integer('strokes_file.photo_type_id'),
                    $request->integer('strokes_file.photo_subtype_id'),
                    'public',
                    'production_order'
                );
            }

            $production_order->color()->sync([$request->input('color_id')]);

            // Lo que la edición quita (destino en 0, pieza o rollo quitado); el Request ya impidió hacerlo en un lote empezado
            $removed = app(ProductionOrderCurveService::class)->removedDetails($production_order->id, $request->input('production_order_details', []));

            if ($request->boolean('reasigned_curve')) {
                app(ProductionOrderCurveService::class)->reassign($request->input('reassigned_origins', []), $production_order->id);
            }

            $this->save_details($request->input('production_order_details', []), $production_order->id);

            ProductionOrderDetailQuantities::whereIn('production_order_detail_id', $removed['zero_products'])->update(['quantity' => 0]);
            ProductionOrderDetail::whereIn('id', $removed['delete'])->get()->each->delete();

            return $this->successResponse(
                new ProductionOrderResource($production_order),
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

    public function save_details(array $production_order_details, int $production_order_id)
    {
        try {
            foreach ($production_order_details as $production_order_detail_item) {

                $model_type = $production_order_detail_item['model_type'];

                if ($model_type === Product::class) {
                    $sizes = $production_order_detail_item['sizes'] ?? [];
                    if (array_sum(array_column($sizes, 'quantity')) === 0) {
                        continue;
                    }
                }

                if ($model_type === Supply::class) {

                    if (! empty($production_order_detail_item['rows'])) {

                        $quantities = [];

                        foreach ($production_order_detail_item['rows'] as $row) {
                            $quantities = array_merge($quantities, $row['sizes'] ?? []);
                        }

                        if (array_sum(array_column($quantities, 'quantity')) === 0) {
                            continue;
                        }

                    } else {
                        $quantity = data_get($production_order_detail_item, 'settings.quantity');

                        if ($quantity === null || $quantity <= 0) {
                            continue;
                        }
                    }
                }

                if ($model_type === Piece::class) {

                    $quantity = data_get($production_order_detail_item, 'settings.quantity');

                    if ($quantity === null || $quantity <= 0) {
                        continue;
                    }
                }

                $production_order_detail = ProductionOrderDetail::firstOrCreate(
                    [
                        'production_order_id' => $production_order_id,
                        'model_type' => $model_type,
                        'model_id' => $production_order_detail_item['model_id'],
                        'destination' => $production_order_detail_item['destination'] ?? null,
                    ]
                );

                if ($model_type === Supply::class) {

                    if (! empty($production_order_detail_item['rows'])) {

                        foreach ($production_order_detail_item['rows'] as $row) {
                            foreach ($row['sizes'] as $size) {
                                ProductionOrderDetailQuantities::updateOrCreate([
                                    'id' => $size['id'],
                                ], [
                                    'production_order_detail_id' => $production_order_detail->id,
                                    'size_id' => $size['size_id'],
                                    'quantity' => $size['quantity'],
                                ]);
                            }
                        }

                    } else {
                        $production_order_detail->settings = $production_order_detail_item['settings'] ?? [];
                        $production_order_detail->save();
                    }
                }

                if ($model_type === Product::class) {

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

                if ($model_type === Piece::class) {
                    $production_order_detail->settings = $production_order_detail_item['settings'] ?? [];
                    $production_order_detail->save();
                }
            }

        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/technical_sheets/production_orders/pdf/{uuid}/{name}",
     *     tags={"Technical Sheets › Production Orders"},
     *     summary="Ver el PDF de una orden de producción (enlace firmado temporal)",
     *     description="Entrega el PDF de la orden sin pedir sesión: la ruta está protegida por **firma** en la dirección (`signature`, sin vencimiento), no por token. La dirección con `{name}` la envía la lista de órdenes en `pdf_url`; la del código QR del documento va sin `name`. En los dos casos genera el PDF en ese momento. El PDF (A4 horizontal, nombre `PO-<referencia>-<corte>.pdf`) lleva el diseño de la orden y, al final, las páginas del PDF de trazos si existe. Responde el archivo, no JSON.",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Identificador público (`uuid`) de la orden, el que trae la lista en `uuid`. Debe existir.",
     *         @OA\Schema(type="string", format="uuid", example="9b2f3a54-6c1e-4d57-8a0f-1e2d3c4b5a69")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="Nombre del archivo (`PO-<referencia>-<corte>.pdf`), para que el navegador lo muestre y lo guarde con ese nombre.",
     *         @OA\Schema(type="string", example="PO-LM4521-A.pdf")
     *     ),
     *     @OA\Parameter(
     *         name="signature",
     *         in="query",
     *         required=true,
     *         description="Firma de la dirección; la agrega el sistema al generar el enlace.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF de la orden de producción (se muestra en línea).",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Enlace sin firma, con firma inválida o vencido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="El enlace no es válido."),
     *             @OA\Property(property="error", type="string", example="El enlace no es válido.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No existe una orden con ese `uuid`.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"id": "Identificador de la orden de producción"}, "errors": {"uuid": {"No hay ningún registro."}}}
     *         )
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/technical_sheets/production_orders/pdf/{uuid}",
     *     tags={"Technical Sheets › Production Orders"},
     *     summary="Ver el PDF de una orden de producción (enlace firmado del código QR)",
     *     description="Misma ruta que `/pdf/{uuid}/{name}` con el `name` omitido (la ruta lo declara opcional): es el enlace firmado sin vencimiento que lleva el código QR impreso en el PDF. Siempre genera el PDF de nuevo. Responde el archivo, no JSON.",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Identificador público (`uuid`) de la orden, el que trae la lista en `uuid`. Debe existir.",
     *         @OA\Schema(type="string", format="uuid", example="9b2f3a54-6c1e-4d57-8a0f-1e2d3c4b5a69")
     *     ),
     *     @OA\Parameter(
     *         name="signature",
     *         in="query",
     *         required=true,
     *         description="Firma de la dirección; la agrega el sistema al generar el enlace.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF de la orden de producción (se muestra en línea).",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Enlace sin firma, con firma inválida o vencido.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="El enlace no es válido."),
     *             @OA\Property(property="error", type="string", example="El enlace no es válido.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No existe una orden con ese `uuid`.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"id": "Identificador de la orden de producción"}, "errors": {"uuid": {"No hay ningún registro."}}}
     *         )
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function pdf(ProductionOrderExportPDFRequest $request, string $uuid)
    {
        try {
            $production_order = ProductionOrder::with([
                'technical_sheet' => [
                    'collection',
                    'subgroup',
                    'color',
                    'waistband_type',
                    'garment_type',
                    'wash_tone',
                    'boot_type',
                    'yoke_type',
                    'back_type',
                    'technical_sheet_details' => ['typology'],
                    'pattern_maker' => ['person', 'position'],
                    'supplies' => ['supply_type'],
                    'product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']],
                    'products' => ['trademark'],
                ],
                'production_order_details' => ['production_order_detail_quantities', 'model'],
                'fabric' => ['model'],
                'pieces',
                'rolls' => ['model' => ['supply_type', 'production_order_details']],
                'derivatives' => ['production_order' => ['technical_sheet' => ['product']]],
                'reassignments' => ['model' => ['technical_sheet' => ['product']]],
                'supplier',
            ])->where('uuid', $uuid)->firstOrFail();

            $pdfUrl = URL::signedRoute(
                'production_orders.technical_sheets.pdf',
                [
                    'uuid' => $production_order->uuid,
                ]
            );

            $qr = app(QrCodeService::class)->generate($pdfUrl);

            $name = $production_order->pdfName();

            // Se imprime con Chrome en A4 horizontal, con el diseño de las fichas; los trazos se pegan después con FPDI
            $image = fn (string $path) => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($path)));
            $output = app(ChromePdfService::class)->render(
                view('pdf.ProductionOrder.pdf', [
                    'production_order' => $production_order,
                    'logo' => $image('images/logo.png'),
                    'qr' => $qr,
                    'name' => $name,
                ])->render(),
                [
                    'footer' => view('pdf.ProductionOrder.pdf', ['footer' => true, 'kanri' => $image('images/logo-kanri-dark.png'), 'code' => 'GP-FR-05', 'version' => '02'])->render(),
                    'margin_top' => 12,
                    'margin_bottom' => 22,
                    'margin_side' => 20,
                    'landscape' => true,
                ]
            );

            $tempDir = storage_path('app/temp');

            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $mainPdf = $tempDir.'/orden_'.$production_order->id.'.pdf';

            file_put_contents(
                $mainPdf,
                $output
            );

            $fpdi = new Fpdi;

            $pageCount = $fpdi->setSourceFile($mainPdf);

            for ($page = 1; $page <= $pageCount; $page++) {

                $tpl = $fpdi->importPage($page);

                $size = $fpdi->getTemplateSize($tpl);

                $fpdi->AddPage(
                    $size['orientation'],
                    [$size['width'], $size['height']]
                );

                $fpdi->useTemplate($tpl);
            }

            if ($production_order->strokes) {

                $stroke = $production_order->strokes;

                $strokePath = public_path(
                    str_replace(
                        url('/'),
                        '',
                        parse_url($stroke->path, PHP_URL_PATH)
                    )
                );

                if (file_exists($strokePath)) {

                    $pageCount = $fpdi->setSourceFile($strokePath);

                    for ($page = 1; $page <= $pageCount; $page++) {

                        $tpl = $fpdi->importPage($page);

                        $size = $fpdi->getTemplateSize($tpl);

                        $fpdi->AddPage(
                            $size['orientation'],
                            [$size['width'], $size['height']]
                        );

                        $fpdi->useTemplate($tpl);
                    }
                }
            }

            @unlink($mainPdf);

            // FPDI arma un PDF nuevo, así que el título se vuelve a poner aquí
            $fileName = $name.'.pdf';

            $fpdi->SetTitle($name, true);

            return response(
                $fpdi->Output('S'),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$fileName.'"',
                ]
            );
        } catch (\Throwable $e) {

            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
