<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheet\TechnicalSheetExportPDFRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetFindRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetUpdateRequest;
use App\Http\Requests\TechnicalSheet\Transformation\TransformationStoreRequest;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\TechnicalSheet\TechnicalSheetResource;
use App\Models\File;
use App\Models\Process;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantities;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Services\FileService;
use App\Services\ProductionOrderCurveService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use App\Services\QrCodeService;
use App\Traits\ModelTypes;

/**
 * @OA\Schema(
 *     schema="TransformationResponse",
 *     type="object",
 *     description="Respuesta de crear una transformación: el producto nuevo creado (no la ficha ni el lote).",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="product",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=318),
 *             @OA\Property(property="code", type="string", example="XJ2210"),
 *             @OA\Property(property="trademark_id", type="integer", example=1101),
 *             @OA\Property(property="subcategory_id", type="integer", example=1205),
 *             @OA\Property(property="created_at", type="string", example="2026-05-14 09:32:10"),
 *             @OA\Property(property="updated_at", type="string", example="2026-05-14 09:32:10"),
 *             @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *             @OA\Property(property="trademark", ref="#/components/schemas/Subitem"),
 *             @OA\Property(property="subcategory", ref="#/components/schemas/Subitem")
 *         ),
 *         @OA\Property(property="status_technical_sheet", type="array", @OA\Items(type="string"), example={"Pendiente", "En revision", "Aprobado", "Cancelado"}),
 *         @OA\Property(property="status_technical_sheet_detail", type="array", @OA\Items(type="string"), example={"Pendiente", "En revision", "Aprobado"}),
 *         @OA\Property(property="model_types", type="array", description="Catálogo de modelos que pueden alimentar las tablas dinámicas.", @OA\Items(type="object", additionalProperties=true))
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="TransformationRequest",
 *     type="object",
 *     required={"technical_sheet_id", "product", "technical_sheet", "production_order"},
 *     description="Crea, a partir de una ficha técnica original, una referencia nueva: producto, ficha, producto STARA opcional y lote con su curva. Se envía como `multipart/form-data` (las fotos son opcionales: si no se sube `file`, se copia la foto de la ficha original con `photo_d.id` / `photo_t.id`).",
 *     @OA\Property(property="technical_sheet_id", type="integer", description="Ficha técnica original de la que se deriva la nueva (queda en `model_id` de la ficha nueva).", example=905),
 *     @OA\Property(property="reasigned_curve", type="boolean", description="Si es verdadero, `reassigned_origins` es obligatorio y se reasigna curva de esos lotes al lote nuevo. Ojo: el nombre del campo lleva una sola `s` (`reasigned`).", example=false),
 *     @OA\Property(
 *         property="reassigned_origins",
 *         type="array",
 *         description="Lotes de origen de la reasignación. Obligatorio si `reasigned_curve` es verdadero. No admite lotes que ya se empezaron a trabajar o están programados.",
 *         @OA\Items(
 *             type="object",
 *             required={"production_order_id", "selected_rows", "reassigned_details"},
 *             @OA\Property(property="production_order_id", type="integer", description="Orden de producción de origen.", example=1204),
 *             @OA\Property(property="selected_rows", type="array", description="Destinos seleccionados.", @OA\Items(type="string", enum={"NACIONAL", "MEDELLIN", "STARA"}), example={"NACIONAL"}),
 *             @OA\Property(property="reassigned_details", type="array", description="Detalle de lo reasignado (estructura que consume `ProductionOrderCurveService`).", @OA\Items(type="object", additionalProperties=true))
 *         )
 *     ),
 *     @OA\Property(
 *         property="product",
 *         type="object",
 *         required={"code", "trademark_id", "subcategory_id"},
 *         description="Producto nuevo. El código no lleva guion y debe cumplir alguna de las validaciones de la marca; la ficha debe llevar el código con guion que resulte del formato de la marca (por ejemplo `XJ-2210`).",
 *         @OA\Property(property="code", type="string", description="Único entre los productos.", example="XJ2210"),
 *         @OA\Property(property="trademark_id", type="integer", description="Marca.", example=1101),
 *         @OA\Property(property="subcategory_id", type="integer", description="Subcategoría.", example=1205)
 *     ),
 *     @OA\Property(
 *         property="product_stara",
 *         type="object",
 *         nullable=true,
 *         description="Producto STARA opcional. Su marca la fija el servidor (STARA DENIM); su subcategoría debe ser la misma de `product`. Su código no puede repetir el de `product` ni existir ya.",
 *         @OA\Property(property="code", type="string", example="ST2210"),
 *         @OA\Property(property="subcategory_id", type="integer", example=1205)
 *     ),
 *     @OA\Property(
 *         property="technical_sheet",
 *         type="object",
 *         required={"date", "status", "code", "collection_id", "garment_type_id", "subgroup_id", "wash_tone_id", "color_id", "waistband_type_id", "measure_of_waistband", "boot_type_id", "yoke_type_id", "back_type_id", "pattern_maker_id", "physical_sample", "number_of_buttons", "photo_d", "photo_t", "supplies", "technical_sheet_details"},
 *         description="Datos de la ficha nueva. Mismos campos y reglas que `TechnicalSheetRequest` (sin `product_id`, que es el producto recién creado), más `status`.",
 *         @OA\Property(property="date", type="string", format="date", description="Formato `Y-m-d`, no posterior a hoy.", example="2026-05-14"),
 *         @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado", "Cancelado"}, example="Pendiente"),
 *         @OA\Property(property="code", type="string", maxLength=11, example="XJ-2210"),
 *         @OA\Property(property="collection_id", type="integer", example=1201),
 *         @OA\Property(property="garment_type_id", type="integer", example=1302),
 *         @OA\Property(property="subgroup_id", type="integer", example=1403),
 *         @OA\Property(property="wash_tone_id", type="integer", example=1504),
 *         @OA\Property(property="color_id", type="integer", example=1605),
 *         @OA\Property(property="waistband_type_id", type="integer", example=1706),
 *         @OA\Property(property="measure_of_waistband", type="number", minimum=1, example=34),
 *         @OA\Property(property="boot_type_id", type="integer", example=1807),
 *         @OA\Property(property="yoke_type_id", type="integer", example=1908),
 *         @OA\Property(property="back_type_id", type="integer", example=2009),
 *         @OA\Property(property="pattern_maker_id", type="integer", example=42),
 *         @OA\Property(property="physical_sample", type="boolean", example=true),
 *         @OA\Property(property="number_of_buttons", type="number", minimum=1, example=5),
 *         @OA\Property(property="observation", type="string", nullable=true, maxLength=1000, example="DERIVADA DEL LOTE ORIGINAL"),
 *         @OA\Property(property="description", type="string", nullable=true, maxLength=1000, example="JEAN SLIM FIT TIRO MEDIO"),
 *         @OA\Property(
 *             property="photo_d",
 *             type="object",
 *             description="Foto delantera. Con `file` se sube una nueva; sin `file`, con `id` se copia la foto de la ficha original.",
 *             @OA\Property(property="file", type="string", format="binary", description="Imagen de máximo 2 MB."),
 *             @OA\Property(property="id", type="integer", description="Id del archivo de la foto original a copiar.", example=3120),
 *             @OA\Property(property="photo_type_id", type="integer", example=3),
 *             @OA\Property(property="photo_subtype_id", type="integer", example=9)
 *         ),
 *         @OA\Property(
 *             property="photo_t",
 *             type="object",
 *             description="Foto trasera. Con `file` se sube una nueva; sin `file`, con `id` se copia la foto de la ficha original.",
 *             @OA\Property(property="file", type="string", format="binary", description="Imagen de máximo 2 MB."),
 *             @OA\Property(property="id", type="integer", description="Id del archivo de la foto original a copiar.", example=3121),
 *             @OA\Property(property="photo_type_id", type="integer", example=3),
 *             @OA\Property(property="photo_subtype_id", type="integer", example=10)
 *         ),
 *         @OA\Property(property="supplies", type="array", description="Ids de insumos; uno por cada tipo de insumo marcado `in_technical_sheet`.", @OA\Items(type="integer"), example={61, 74}),
 *         @OA\Property(property="technical_sheet_details", type="array", description="Detalles por subproceso u operación, con la misma forma que en `TechnicalSheetRequest`.", @OA\Items(type="object", additionalProperties=true))
 *     ),
 *     @OA\Property(
 *         property="production_order",
 *         type="object",
 *         required={"cut", "status", "production_order_details"},
 *         description="Lote nuevo con su curva.",
 *         @OA\Property(property="cut", type="string", maxLength=2, description="Lote en MAYÚSCULAS, de máximo 2 caracteres.", example="A"),
 *         @OA\Property(property="status", type="string", enum={"Pendiente", "Aprobado", "Cancelado"}, example="Pendiente"),
 *         @OA\Property(
 *             property="production_order_details",
 *             type="array",
 *             description="Entre 1 y 5 destinos con su curva. Cada destino debe tener al menos una cantidad mayor que 0.",
 *             @OA\Items(
 *                 type="object",
 *                 required={"model_type", "destination", "sizes"},
 *                 @OA\Property(property="model_type", type="string", enum={"App\Models\Product"}, example="App\Models\Product"),
 *                 @OA\Property(property="model_id", type="integer", nullable=true, description="Lo resuelve el servidor: el producto nuevo o el STARA según `destination`.", example=null),
 *                 @OA\Property(property="destination", type="string", enum={"NACIONAL", "MEDELLIN", "STARA"}, example="NACIONAL"),
 *                 @OA\Property(
 *                     property="sizes",
 *                     type="array",
 *                     description="Cantidad por talla (no se valida en las reglas, pero se usa al guardar).",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="size_id", type="integer", example=101),
 *                         @OA\Property(property="quantity", type="integer", example=24)
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */
class TransformationController extends Controller
{
    use ApiMessage, ApiResponser, ModelTypes;

    /**
     * @OA\Get(
     *     path="/technical_sheets/transformations/find/{id}",
     *     tags={"Technical Sheets › Transformations"},
     *     summary="Obtener la ficha técnica de una transformación",
     *     description="Devuelve una ficha técnica con el mismo formato que `GET /technical_sheets/find`. Ojo: el `{id}` de la URL **no se usa**; la ficha se busca con **uno** de los parámetros de consulta `id`, `product_id` o `uuid` (se exige exactamente uno; sin ellos responde 422). A diferencia de la ficha normal, no carga la tipología de los detalles. Permiso: `technical_sheets.find` o `technical_sheets.transformations.find`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Sin efecto (ver descripción).", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="id", in="query", required=false, description="Id de la ficha. No se combina con `product_id` ni `uuid`.", @OA\Schema(type="integer", example=905)),
     *     @OA\Parameter(name="product_id", in="query", required=false, description="Id del producto de la ficha. No se combina con `id` ni `uuid`.", @OA\Schema(type="integer", example=318)),
     *     @OA\Parameter(name="uuid", in="query", required=false, description="UUID de la ficha. No se combina con `id` ni `product_id`.", @OA\Schema(type="string", format="uuid", example="3f8c2a10-5b7d-4e6a-9c21-8d4e0f1a7b52")),
     *     @OA\Response(
     *         response=200,
     *         description="Ficha técnica cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TechnicalSheetResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se envió exactamente uno de los parámetros, o la ficha no existe.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador de la ficha técnica", "product_id": "Identificador del producto en la ficha técnica", "uuid": "Código uuid de la ficha técnica"},
     *                 "errors": {"id": {"Este campo es obligatorio."}, "product_id": {"Este campo es obligatorio."}, "uuid": {"Este campo es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(TechnicalSheetFindRequest $request, $id = null)
    {
        try {
            $technical_sheet = TechnicalSheet::with([
                'collection',
                'subgroup',
                'color',
                'waistband_type',
                'garment_type',
                'wash_tone',
                'boot_type',
                'yoke_type',
                'back_type',
                'technical_sheet_details',
                'pattern_maker' => ['person', 'position'],
                'supplies' => ['supply_type'],
                'product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']],
                'products' => ['trademark']
            ])
                ->when($request->filled('id'), function ($query) use ($request) {
                    $query->where('id', $request->integer('id'));
                })
                ->when($request->filled('product_id'), function ($query) use ($request) {
                    return $query->where('product_id', $request->integer('product_id'));
                })
                ->when($request->filled('uuid'), function ($query) use ($request) {
                    return $query->where('uuid', $request->input('uuid'));
                })->firstOrFail();

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet),
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
     *     path="/technical_sheets/transformations/store",
     *     tags={"Technical Sheets › Transformations"},
     *     summary="Crear una transformación",
     *     description="Desde una ficha técnica original crea una referencia nueva derivada, todo en una sola petición: el producto nuevo, su ficha técnica (con `model_id` apuntando a la original, consecutivo y `uuid` generados, insumos, fotos subidas o copiadas de la original y detalles por proceso), un producto STARA opcional ligado a la ficha, y un lote con su curva por destino. Si `reasigned_curve` es verdadero, además reasigna curva de los lotes de `reassigned_origins` al lote nuevo. Responde con el **producto** creado, no con la ficha. Se envía como `multipart/form-data`. Permiso: `technical_sheets.transformations.store`.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/TransformationRequest"))
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transformación creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TransformationResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"product.code": "Código de la Referencia del Producto", "product.trademark_id": "Marca", "product.subcategory_id": "Subcategoría", "technical_sheet.date": "Fecha de aporbación del producto"},
     *                 "errors": {"product.code": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(TransformationStoreRequest $request)
    {
        try {
            $product = new Product();

            $product->code = $request->input('product.code');
            $product->trademark_id = $request->input('product.trademark_id');
            $product->subcategory_id = $request->input('product.subcategory_id');

            $product->save();

            $result = DB::select('CALL generate_technical_sheet_consecutive()');
            $technical_sheet = new TechnicalSheet();

            $technical_sheet->uuid = Str::uuid();
            $technical_sheet->consecutive = $result[0]->consecutive;
            $technical_sheet->status = $request->input('technical_sheet.status');
            $technical_sheet->date = $request->input('technical_sheet.date');
            $technical_sheet->product_id = $product->id;
            $technical_sheet->model_id = $request->input('technical_sheet_id');
            $technical_sheet->model_type = TechnicalSheet::class;
            $technical_sheet->code = $request->input('technical_sheet.code');
            $technical_sheet->collection_id = $request->integer('technical_sheet.collection_id');
            $technical_sheet->garment_type_id = $request->integer('technical_sheet.garment_type_id');
            $technical_sheet->subgroup_id = $request->integer('technical_sheet.subgroup_id');
            $technical_sheet->wash_tone_id = $request->integer('technical_sheet.wash_tone_id');
            $technical_sheet->color_id = $request->integer('technical_sheet.color_id');
            $technical_sheet->waistband_type_id = $request->integer('technical_sheet.waistband_type_id');
            $technical_sheet->measure_of_waistband = $request->integer('technical_sheet.measure_of_waistband');
            $technical_sheet->boot_type_id = $request->integer('technical_sheet.boot_type_id');
            $technical_sheet->yoke_type_id = $request->integer('technical_sheet.yoke_type_id');
            $technical_sheet->back_type_id = $request->integer('technical_sheet.back_type_id');
            $technical_sheet->physical_sample = $request->integer('technical_sheet.physical_sample');
            $technical_sheet->number_of_buttons = $request->integer('technical_sheet.number_of_buttons');
            $technical_sheet->pattern_maker_id = $request->integer('technical_sheet.pattern_maker_id');
            $technical_sheet->description = $request->input('technical_sheet.description');
            $technical_sheet->observation = $request->input('technical_sheet.observation');

            $technical_sheet->save();

            $technical_sheet->auditSync('supplies', $request->input('technical_sheet.supplies', []));

            if ($request->hasFile('technical_sheet.photo_d.file')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('technical_sheet.photo_d.file'),
                    $request->integer('technical_sheet.photo_d.photo_type_id'),
                    $request->integer('technical_sheet.photo_d.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            } elseif ($request->input('technical_sheet.photo_d.id')) {
                $original_file = File::find($request->input('technical_sheet.photo_d.id'));

                if ($original_file) {
                    $new_file = $original_file->replicate();

                    $new_file->model_id = $technical_sheet->id;
                    $new_file->model_type = TechnicalSheet::class;

                    $new_file->name = $request->input('product.code') . '-D';

                    $new_file->save();
                }
            }

            if ($request->hasFile('technical_sheet.photo_t.file')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('technical_sheet.photo_t.file'),
                    $request->integer('technical_sheet.photo_t.photo_type_id'),
                    $request->integer('technical_sheet.photo_t.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            } elseif ($request->input('technical_sheet.photo_t.id')) {
                $original_file = File::find($request->input('technical_sheet.photo_t.id'));

                if ($original_file) {
                    $new_file = $original_file->replicate();

                    $new_file->model_id = $technical_sheet->id;
                    $new_file->model_type = TechnicalSheet::class;

                    $new_file->name = $request->input('product.code') . '-T';

                    $new_file->save();
                }
            }

            $this->save($request->input('technical_sheet.technical_sheet_details', []), $technical_sheet->id);

            $product_stara = null;

            if (!empty($request->input('product_stara'))) {
                $product_stara = new Product();

                $product_stara->code = $request->input('product_stara.code');
                $product_stara->trademark_id = $request->input('product_stara.trademark_id');
                $product_stara->subcategory_id = $request->input('product_stara.subcategory_id');

                $product_stara->save();

                $product_stara->auditAttach('technical_sheets', $technical_sheet->id);
            }

            $result = DB::select("CALL generate_production_order_consecutive()");
            $production_order = new ProductionOrder();
            $consecutive = $result[0]->consecutive;

            $production_order->uuid = Str::uuid();
            $production_order->consecutive = $consecutive;
            $production_order->technical_sheet_id = $technical_sheet->id;
            $production_order->cut = $request->input('production_order.cut');
            $production_order->status = $request->input('production_order.status');

            $production_order->save();

            $this->save_details_production_order($request->input('production_order.production_order_details', []), $production_order->id, $product->id, $product_stara?->id);

            if ($request->boolean('reasigned_curve')) {
                app(ProductionOrderCurveService::class)->reassign($request->input('reassigned_origins', []), $production_order->id);
            }

            return $this->successResponse(
                new ProductResource($product),
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
     *     path="/technical_sheets/transformations/update/{id}",
     *     tags={"Technical Sheets › Transformations"},
     *     summary="Editar la ficha técnica de una transformación",
     *     description="Comparte el Request de `PUT /technical_sheets/update/{id}` (mismos campos y validaciones), por lo que acepta `removed_details`, pero esta ruta **no** lo aplica: no quita subprocesos ni operaciones. Reemplaza los datos de la ficha, sincroniza insumos, guarda las fotos que lleguen y actualiza los detalles por proceso, sin descartar los subprocesos automáticos que no aplican. Para adjuntar fotos el frontend envía **POST** a esta URL con `_method=PUT`. Permiso: `technical_sheets.update` o `technical_sheets.transformations.update`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/TechnicalSheetUpdateRequest"),
     *                     @OA\Schema(@OA\Property(property="_method", type="string", enum={"PUT"}, description="Solo si se envía como POST."))
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ficha técnica editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TechnicalSheetResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o ficha no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"date": "Fecha de aporbación del producto", "removed_details": "Subprocesos y operaciones quitados de la ficha"},
     *                 "errors": {"date": {"Debe ser una fecha anterior o igual a 2026-11-25."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(TechnicalSheetUpdateRequest $request, $id)
    {
        try {
            $technical_sheet = TechnicalSheet::findOrFail($id);

            // Este Request ya no exige todos los campos: solo se actualiza lo que llega, lo demás queda como está
            foreach (['date', 'code', 'description', 'observation', 'status'] as $field) {
                if ($request->has($field)) {
                    $technical_sheet->{$field} = $request->input($field);
                }
            }

            foreach (['product_id', 'collection_id', 'garment_type_id', 'subgroup_id', 'wash_tone_id', 'color_id', 'waistband_type_id', 'measure_of_waistband', 'boot_type_id', 'yoke_type_id', 'back_type_id', 'physical_sample', 'number_of_buttons', 'pattern_maker_id'] as $field) {
                if ($request->has($field)) {
                    $technical_sheet->{$field} = $request->integer($field);
                }
            }

            $technical_sheet->save();

            if ($request->has('supplies')) {
                $technical_sheet->auditSync('supplies', $request->input('supplies', []));
            }

            if ($request->hasFile('photo_d')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('photo_d.file'),
                    $request->integer('photo_d.photo_type_id'),
                    $request->integer('photo_d.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            }

            if ($request->hasFile('photo_t')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('photo_t.file'),
                    $request->integer('photo_t.photo_type_id'),
                    $request->integer('photo_t.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            }

            if ($request->has('technical_sheet_details')) {
                $this->save($request->input('technical_sheet_details', []), $technical_sheet->id);
            }

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet),
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

    private function save(array $technical_sheet_details, int $technical_sheet_id)
    {
        // Sin try/catch propio: si falla, el error llega al de store/update y la respuesta no dice «éxito»
        foreach ($technical_sheet_details as $technical_sheet_detail) {
            $detail = TechnicalSheetDetail::updateOrCreate(
                [
                    'technical_sheet_id' => $technical_sheet_id,
                    'model_type' => $technical_sheet_detail['model_type'],
                    'model_id' => $technical_sheet_detail['model_id'],
                ],
                [
                    'status' => $technical_sheet_detail['status'],
                    'settings' => $technical_sheet_detail['settings'] ?? (object) [],
                ]
            );

            if (isset($technical_sheet_detail['typology'])) {
                $detail->auditSync('typology', $technical_sheet_detail['typology']);
            }
        }
    }

    public function save_details_production_order(array $production_order_details, int $production_order_id, int $product_id, ?int $product_stara_id = null)
    {
        foreach ($production_order_details as $item) {

            $quantities = $item['sizes'];

            if (array_sum(array_column($quantities, 'quantity')) === 0) {
                continue;
            }

            $model_id = $item['destination'] === 'STARA'
                ? $product_stara_id
                : $product_id;

            $production_order_detail = ProductionOrderDetail::firstOrCreate(['production_order_id' => $production_order_id, 'model_type' => Product::class, 'model_id' => $model_id, 'destination' => $item['destination']]);

            foreach ($item['sizes'] as $size) {
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

    private function resolveDynamicField($field, $value)
    {

        if (empty($field->model) || empty($value) || !class_exists($field->model)) {
            return $value;
        }

        $model = $field->model;

        $record = $model::find($value);

        if (!$record) {
            return $value;
        }

        $config = collect($this->getModelTypes())
            ->firstWhere('model', $model);

        if (!$config) {
            return $record->id;
        }

        $option = $config['option'] ?? 'name';

        if (is_array($option)) {
            return collect($option)
                ->map(fn($path) => data_get($record, $path))
                ->filter()
                ->implode(' ');
        }

        return data_get($record, $option);
    }

    private function formatTechnicalSheetDetails($details)
    {
        return $details->map(function ($detail) {
            $settings = $detail->settings;

            if (
                isset($settings->dinamic) &&
                !empty($settings->dinamic->values) &&
                !empty($settings->dinamic->body)
            ) {

                $body = collect($settings->dinamic->body);

                $settings->dinamic->values = collect($settings->dinamic->values)
                    ->map(function ($row) use ($body) {
                        foreach (get_object_vars($row) as $field => $value) {
                            $definition = $body->firstWhere('field', $field);

                            if (!$definition) {
                                continue;
                            }

                            if (($definition->type ?? null) === 'boolean') {
                                $row->{$field} = $value ? 'Sí' : 'No';
                                continue;
                            }

                            $row->{$field} = $this->resolveDynamicField(
                                $definition,
                                $value
                            );
                        }

                        return $row;
                    })
                    ->values()
                    ->toArray();
            }

            if (
                isset($settings->static) &&
                !empty($settings->static->values) &&
                !empty($settings->static->body)
            ) {

                $body = collect($settings->static->body)
                    ->flatten(1);

                foreach (get_object_vars($settings->static->values) as $field => $value) {

                    $definition = $body->firstWhere('field', $field);

                    if (!$definition) {
                        continue;
                    }

                    if (($definition->type ?? null) === 'boolean') {
                        $settings->static->values->{$field} = $value ? 'Sí' : 'No';
                        continue;
                    }

                    $settings->static->values->{$field} = $this->resolveDynamicField(
                        $definition,
                        $value
                    );
                }
            }

            $detail->settings = $settings;

            return $detail;
        });
    }

    public function pdf(TechnicalSheetExportPDFRequest $request, string $uuid)
    {
        try {
            $technical_sheet = TechnicalSheet::with([
                'collection',
                'subgroup',
                'color',
                'waistband_type',
                'garment_type',
                'wash_tone',
                'boot_type',
                'yoke_type',
                'back_type',
                'technical_sheet_details',
                'pattern_maker' => ['person', 'position'],
                'supplies' => ['supply_type'],
                'product' => ['trademark' => ['group'], 'subcategory' => ['category']],
                'products' => ['trademark']
            ])->where('uuid', $uuid)->firstOrFail();

            $production_order = ProductionOrder::with([
                'technical_sheet' => ['product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']], 'products', 'photo_d', 'photo_t', 'garment_type', 'collection'],
                'production_order_details' => ['production_order_detail_quantities', 'model'],
                'fabric' => ['model'],
                'pieces',
                'rolls'
            ])->where('id', $request->production_order_id)
                ->where('technical_sheet_id', $technical_sheet->id)
                ->firstOrFail();

            $pdfUrl = URL::signedRoute(
                'technical_sheets.pdf',
                [
                    'uuid' => $technical_sheet->uuid,
                    'production_order_id' => $production_order->id,
                ]
            );

            $qr = app(QrCodeService::class)->generate($pdfUrl);

            $technical_sheet->technical_sheet_details =
                $this->formatTechnicalSheetDetails(
                    $technical_sheet->technical_sheet_details
                );

            $processes = Process::with(['subprocesses' => ['operations']])->where('settings->in_technical_sheet', true)
                ->get();

            $pdf = Pdf::loadView(
                'pdf.TechnicalSheet.technical_sheet',
                [
                    'technical_sheet' => $technical_sheet,
                    'production_order' => $production_order,
                    'processes' => $processes,
                    'qr' => $qr,
                ]
            )->setPaper('a4', 'portrait');

            $dompdf = $pdf->getDomPDF();

            $dompdf->render();

            $canvas = $dompdf->getCanvas();

            $canvas->page_text(
                270,
                815,
                "Página {PAGE_NUM} de {PAGE_COUNT}",
                null,
                9,
                [0, 0, 0]
            );

            $has_stara = $production_order->production_order_details->contains(
                fn($item) => $item->destination === 'STARA'
            );

            $fileName = 'TS-' .
                ($production_order->technical_sheet->product->code ?? 'SIN-CODIGO') .
                '-' .
                ($production_order->cut ?? 'SIN-LOTE');

            if ($has_stara) {
                $staraReference = optional($production_order->technical_sheet->products->first())->code;

                if ($staraReference) {
                    $fileName .= ', ' . $staraReference . '-' . ($production_order->cut ?? 'SIN-LOTE');
                }
            }

            $fileName .= '.pdf';

            return response(
                $dompdf->output(),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline;  filename="' . $fileName . '"'
                ]
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
