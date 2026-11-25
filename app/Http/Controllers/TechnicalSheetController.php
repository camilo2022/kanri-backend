<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicalSheet\TechnicalSheetExportPDFRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetFindRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetProcessPdfRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetStoreRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetUpdateRequest;
use App\Http\Resources\TechnicalSheet\TechnicalSheetResource;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\Subprocess;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Services\ChromePdfService;
use App\Services\PdfLinkService;
use App\Services\FileService;
use App\Services\ProductivityDetailService;
use App\Services\QrCodeService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use App\Traits\ModelTypes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="TechnicalSheet",
 *     type="object",
 *     description="Ficha técnica de una referencia (una por producto). Las relaciones salen serializadas tal cual por Eloquent.",
 *     @OA\Property(property="id", type="integer", example=905),
 *     @OA\Property(property="model_id", type="integer", nullable=true, description="Solo en fichas creadas por una transformación: id de la ficha técnica original. `null` en las demás.", example=null),
 *     @OA\Property(property="model_type", type="string", nullable=true, description="Solo en fichas creadas por una transformación: `App\Models\TechnicalSheet`. `null` en las demás.", example=null),
 *     @OA\Property(property="code", type="string", maxLength=11, description="Código de la ficha.", example="FT-2026-014"),
 *     @OA\Property(property="date", type="string", format="date", description="Fecha de aprobación del producto, formato `Y-m-d`.", example="2026-05-14"),
 *     @OA\Property(property="product_id", type="integer", example=312),
 *     @OA\Property(property="collection_id", type="integer", example=1201),
 *     @OA\Property(property="garment_type_id", type="integer", example=1302),
 *     @OA\Property(property="subgroup_id", type="integer", example=1403),
 *     @OA\Property(property="wash_tone_id", type="integer", example=1504),
 *     @OA\Property(property="color_id", type="integer", example=1605),
 *     @OA\Property(property="waistband_type_id", type="integer", example=1706),
 *     @OA\Property(property="measure_of_waistband", type="integer", example=34),
 *     @OA\Property(property="boot_type_id", type="integer", example=1807),
 *     @OA\Property(property="yoke_type_id", type="integer", example=1908),
 *     @OA\Property(property="back_type_id", type="integer", example=2009),
 *     @OA\Property(property="physical_sample", type="boolean", example=true),
 *     @OA\Property(property="number_of_buttons", type="integer", example=5),
 *     @OA\Property(property="description", type="string", nullable=true, example="JEAN SLIM FIT TIRO MEDIO"),
 *     @OA\Property(property="observation", type="string", nullable=true, example="REVISAR TONO DESPUÉS DEL LAVADO"),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado", "Cancelado"}, example="Pendiente"),
 *     @OA\Property(property="pattern_maker_id", type="integer", description="Empleado patronista.", example=42),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 09:32:10"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 09:32:10"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="technical_sheet_details",
 *         type="array",
 *         description="Detalle por subproceso u operación: estado, tablas (`settings.static` / `settings.dinamic`) y tipología.",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=7710),
 *             @OA\Property(property="technical_sheet_id", type="integer", example=905),
 *             @OA\Property(property="model_type", type="string", example="App\Models\Subprocess"),
 *             @OA\Property(property="model_id", type="integer", example=18),
 *             @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado"}, example="Pendiente"),
 *             @OA\Property(property="settings", type="object", additionalProperties=true, description="Tablas del detalle: `static` y/o `dinamic`, cada una con `body`, `values` e `insert_values`."),
 *             @OA\Property(property="typology", type="array", description="Tipologías del detalle (catálogo Typology).", @OA\Items(ref="#/components/schemas/Subitem"))
 *         )
 *     ),
 *     @OA\Property(property="technical_sheet_details_processes", type="array", description="Relación `technical_sheet_details_processes` serializada tal cual.", @OA\Items(type="object", additionalProperties=true)),
 *     @OA\Property(property="supplies", type="array", description="Insumos de la ficha, cada uno con su tipo de insumo (`supply_type`).", @OA\Items(type="object", additionalProperties=true)),
 *     @OA\Property(property="pattern_maker", type="object", nullable=true, description="Empleado patronista con su persona y cargo.", additionalProperties=true),
 *     @OA\Property(property="product", type="object", nullable=true, description="Producto de la ficha con su marca (grupo y tallas) y su subcategoría (categoría).", additionalProperties=true),
 *     @OA\Property(property="products", type="object", nullable=true, description="Primer producto STARA ligado a la ficha, con su marca. `null` si no tiene.", additionalProperties=true),
 *     @OA\Property(property="collection", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="garment_type", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="subgroup", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="wash_tone", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="color", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="waistband_type", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="boot_type", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="yoke_type", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="back_type", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="photo_d", nullable=true, description="Foto delantera. Su nombre es `<código de la referencia>-D`.", allOf={@OA\Schema(ref="#/components/schemas/File")}),
 *     @OA\Property(property="photo_t", nullable=true, description="Foto trasera. Su nombre es `<código de la referencia>-T`.", allOf={@OA\Schema(ref="#/components/schemas/File")})
 * )
 *
 * @OA\Schema(
 *     schema="TechnicalSheetResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="technical_sheet", ref="#/components/schemas/TechnicalSheet"),
 *         @OA\Property(property="status_technical_sheet", type="array", description="Estados posibles de una ficha.", @OA\Items(type="string"), example={"Pendiente", "En revision", "Aprobado", "Cancelado"}),
 *         @OA\Property(property="status_technical_sheet_detail", type="array", description="Estados posibles de un detalle de la ficha.", @OA\Items(type="string"), example={"Pendiente", "En revision", "Aprobado"}),
 *         @OA\Property(property="status_production_order", type="array", description="Estados posibles de una orden de producción.", @OA\Items(type="string"), example={"Pendiente", "Cancelado", "Aprobado"}),
 *         @OA\Property(property="model_types", type="array", description="Catálogo de modelos que pueden alimentar las tablas dinámicas (`getModelTypes()`).", @OA\Items(type="object", additionalProperties=true))
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="TechnicalSheetPdfLinkResponse",
 *     type="object",
 *     description="Respuesta de las rutas `download_*` (con sesión): no devuelve el PDF sino la dirección firmada desde la cual se entrega.",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="url", type="string", description="Dirección firmada y temporal (vence a los 5 minutos) que termina en el nombre del PDF.", example="http://kanri-backend.test/api/technical_sheets/pdf/9b2f3c1e-7a44-4d0e-8f5a-2c61d0b7e913/TS-XJ1042-A.pdf?expires=1795600000&signature=firma")
 *     ),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="TechnicalSheetRequest",
 *     type="object",
 *     required={"date", "code", "product_id", "collection_id", "garment_type_id", "subgroup_id", "wash_tone_id", "color_id", "waistband_type_id", "measure_of_waistband", "boot_type_id", "yoke_type_id", "back_type_id", "pattern_maker_id", "physical_sample", "number_of_buttons", "photo_d", "photo_t", "supplies"},
 *     description="Se envía como `multipart/form-data` para adjuntar las fotos. Los ids de catálogo deben ser de su catálogo y estar activos.",
 *     @OA\Property(property="date", type="string", format="date", description="Formato `Y-m-d`, no posterior a hoy.", example="2026-05-14"),
 *     @OA\Property(property="code", type="string", maxLength=11, description="Único entre las fichas.", example="FT-2026-014"),
 *     @OA\Property(property="product_id", type="integer", description="Producto de la ficha. Cada producto tiene una sola ficha.", example=312),
 *     @OA\Property(property="collection_id", type="integer", description="Colección.", example=1201),
 *     @OA\Property(property="garment_type_id", type="integer", description="Tipo de prenda.", example=1302),
 *     @OA\Property(property="subgroup_id", type="integer", description="Subgrupo.", example=1403),
 *     @OA\Property(property="wash_tone_id", type="integer", description="Tono de lavado.", example=1504),
 *     @OA\Property(property="color_id", type="integer", description="Color.", example=1605),
 *     @OA\Property(property="waistband_type_id", type="integer", description="Tipo de pretina.", example=1706),
 *     @OA\Property(property="measure_of_waistband", type="number", minimum=1, description="Medida de la pretina.", example=34),
 *     @OA\Property(property="boot_type_id", type="integer", description="Tipo de bota.", example=1807),
 *     @OA\Property(property="yoke_type_id", type="integer", description="Tipo de cotilla.", example=1908),
 *     @OA\Property(property="back_type_id", type="integer", description="Tipo de trasero.", example=2009),
 *     @OA\Property(property="pattern_maker_id", type="integer", description="Empleado activo que hace de patronista.", example=42),
 *     @OA\Property(property="physical_sample", type="boolean", description="Indica si hay muestra física.", example=true),
 *     @OA\Property(property="number_of_buttons", type="number", minimum=1, example=5),
 *     @OA\Property(property="observation", type="string", nullable=true, maxLength=1000, example="REVISAR TONO DESPUÉS DEL LAVADO"),
 *     @OA\Property(property="description", type="string", nullable=true, maxLength=1000, example="JEAN SLIM FIT TIRO MEDIO"),
 *     @OA\Property(
 *         property="photo_d",
 *         type="object",
 *         description="Foto delantera. Obligatoria al crear. El archivo debe llamarse `<código de la referencia>-D` (por ejemplo `XJ1042-D.jpg`).",
 *         @OA\Property(property="file", type="string", format="binary", description="Imagen de máximo 2 MB."),
 *         @OA\Property(property="photo_type_id", type="integer", description="Tipo de archivo (no se valida).", example=3),
 *         @OA\Property(property="photo_subtype_id", type="integer", description="Subtipo de archivo (no se valida).", example=9)
 *     ),
 *     @OA\Property(
 *         property="photo_t",
 *         type="object",
 *         description="Foto trasera. Obligatoria al crear. El archivo debe llamarse `<código de la referencia>-T`.",
 *         @OA\Property(property="file", type="string", format="binary", description="Imagen de máximo 2 MB."),
 *         @OA\Property(property="photo_type_id", type="integer", description="Tipo de archivo (no se valida).", example=3),
 *         @OA\Property(property="photo_subtype_id", type="integer", description="Subtipo de archivo (no se valida).", example=10)
 *     ),
 *     @OA\Property(property="supplies", type="array", description="Ids de insumos. Debe haber uno por cada tipo de insumo marcado `in_technical_sheet` y no puede haber dos del mismo tipo.", @OA\Items(type="integer"), example={61, 74}),
 *     @OA\Property(
 *         property="technical_sheet_details",
 *         type="array",
 *         description="Detalles por subproceso u operación. Los que van en secuencia exigen que la ficha tenga alguno de sus anteriores.",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="model_type", type="string", description="Modelo del detalle (no se valida en las reglas, pero se usa al guardar).", example="App\Models\Subprocess"),
 *             @OA\Property(property="model_id", type="integer", description="Id del subproceso u operación (no se valida en las reglas, pero se usa al guardar).", example=18),
 *             @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado"}, description="No se valida en las reglas, pero se usa al guardar.", example="Pendiente"),
 *             @OA\Property(property="typology", type="integer", nullable=true, description="Id de tipología activa.", example=2301),
 *             @OA\Property(property="settings", type="object", additionalProperties=true, description="Tablas: `static` y/o `dinamic`, cada una con `values` e `insert_values` (booleano obligatorio si la tabla viene). Las filas se validan con las reglas de su `body`.")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="TechnicalSheetUpdateRequest",
 *     type="object",
 *     required={"date", "status", "code", "product_id", "collection_id", "garment_type_id", "subgroup_id", "wash_tone_id", "color_id", "waistband_type_id", "measure_of_waistband", "boot_type_id", "yoke_type_id", "back_type_id", "pattern_maker_id", "physical_sample", "number_of_buttons", "photo_d", "photo_t", "technical_sheet_details", "supplies"},
 *     description="Mismos campos que `TechnicalSheetRequest`, con estas diferencias: `status` es obligatorio, `technical_sheet_details` es obligatorio, las fotos son opcionales (si no se envía `file`, se conserva la actual) y se puede enviar `removed_details`. El id de la ficha va en la URL.",
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado", "Cancelado"}, example="En revision"),
 *     @OA\Property(
 *         property="removed_details",
 *         type="array",
 *         description="Subprocesos u operaciones que se quitan de la ficha (se borran con sus operaciones). No se puede quitar uno que esté Aprobado.",
 *         @OA\Items(
 *             type="object",
 *             required={"model_type", "model_id"},
 *             @OA\Property(property="model_type", type="string", enum={"App\Models\Subprocess", "App\Models\Operation"}, example="App\Models\Subprocess"),
 *             @OA\Property(property="model_id", type="integer", example=18)
 *         )
 *     )
 * )
 */
class TechnicalSheetController extends Controller
{
    use ApiMessage, ApiResponser, ModelTypes;

    /**
     * @OA\Get(
     *     path="/technical_sheets/find",
     *     tags={"Technical Sheets"},
     *     summary="Obtener una ficha técnica",
     *     description="Busca una sola ficha por **uno** de estos parámetros (se exige exactamente uno): `id`, `product_id` o `uuid`. Devuelve la ficha con sus catálogos de diseño, patronista, insumos, fotos y detalles por proceso. Permiso: `technical_sheets.find` o `technical_sheets.transformations.find`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="query", required=false, description="Id de la ficha. No se combina con `product_id` ni `uuid`.", @OA\Schema(type="integer", example=905)),
     *     @OA\Parameter(name="product_id", in="query", required=false, description="Id del producto de la ficha. No se combina con `id` ni `uuid`.", @OA\Schema(type="integer", example=312)),
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
                'technical_sheet_details' => ['typology'],
                'technical_sheet_details_processes',
                'pattern_maker' => ['person', 'position'],
                'supplies' => ['supply_type'],
                'product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']],
                'products' => ['trademark'],
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
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/technical_sheets/store",
     *     tags={"Technical Sheets"},
     *     summary="Crear una ficha técnica",
     *     description="Crea la ficha completa de una referencia: datos de diseño, insumos, fotos delantera y trasera y detalles por proceso. El consecutivo lo genera el servidor y la ficha recibe un `uuid` propio. Cada producto admite una sola ficha. Las fotos deben llamarse `<código de la referencia>-D` y `<código de la referencia>-T`. Se envía como `multipart/form-data`. Permiso: `technical_sheets.store`.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/TechnicalSheetRequest"))
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ficha técnica creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TechnicalSheetResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"product_id": "Identificador del producto", "photo_d": "Foto delantera", "photo_t": "Foto trasera"},
     *                 "errors": {"code": {"Ya está registrado."}, "photo_d.file": {"El nombre del archivo debe contener el código de referencia seguido del sufijo D."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(TechnicalSheetStoreRequest $request)
    {
        try {
            $result = DB::select('CALL generate_technical_sheet_consecutive()');
            $technical_sheet = new TechnicalSheet;

            $technical_sheet->uuid = Str::uuid();
            $technical_sheet->code = $request->input('code');
            $technical_sheet->consecutive = $result[0]->consecutive;
            $technical_sheet->date = $request->input('date');
            $technical_sheet->product_id = $request->integer('product_id');
            $technical_sheet->collection_id = $request->integer('collection_id');
            $technical_sheet->garment_type_id = $request->integer('garment_type_id');
            $technical_sheet->subgroup_id = $request->integer('subgroup_id');
            $technical_sheet->wash_tone_id = $request->integer('wash_tone_id');
            $technical_sheet->color_id = $request->integer('color_id');
            $technical_sheet->waistband_type_id = $request->integer('waistband_type_id');
            $technical_sheet->measure_of_waistband = $request->integer('measure_of_waistband');
            $technical_sheet->boot_type_id = $request->integer('boot_type_id');
            $technical_sheet->yoke_type_id = $request->integer('yoke_type_id');
            $technical_sheet->back_type_id = $request->integer('back_type_id');
            $technical_sheet->physical_sample = $request->integer('physical_sample');
            $technical_sheet->number_of_buttons = $request->integer('number_of_buttons');
            $technical_sheet->pattern_maker_id = $request->integer('pattern_maker_id');
            $technical_sheet->description = $request->input('description');
            $technical_sheet->observation = $request->input('observation');

            $technical_sheet->save();

            $technical_sheet->auditSync('supplies', $request->input('supplies', []));

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

            $this->save($request->input('technical_sheet_details', []), $technical_sheet->id, $request->integer('wash_tone_id') ?: null);

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet),
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
     *     path="/technical_sheets/update/{id}",
     *     tags={"Technical Sheets"},
     *     summary="Editar una ficha técnica",
     *     description="Reemplaza los datos de la ficha, sincroniza sus insumos, guarda las fotos que lleguen y actualiza los detalles por proceso. Lo que ya está Aprobado se conserva. `removed_details` quita subprocesos u operaciones de la ficha. Para adjuntar fotos (multipart) el frontend envía **POST** a esta misma URL con el campo `_method=PUT`, porque PHP no lee archivos en un PUT multipart. Permiso: `technical_sheets.update` o `technical_sheets.transformations.update`.",
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

            $technical_sheet->date = $request->input('date');
            $technical_sheet->code = $request->input('code');
            $technical_sheet->product_id = $request->integer('product_id');
            $technical_sheet->collection_id = $request->integer('collection_id');
            $technical_sheet->garment_type_id = $request->integer('garment_type_id');
            $technical_sheet->subgroup_id = $request->integer('subgroup_id');
            $technical_sheet->wash_tone_id = $request->integer('wash_tone_id');
            $technical_sheet->color_id = $request->integer('color_id');
            $technical_sheet->waistband_type_id = $request->integer('waistband_type_id');
            $technical_sheet->measure_of_waistband = $request->integer('measure_of_waistband');
            $technical_sheet->boot_type_id = $request->integer('boot_type_id');
            $technical_sheet->yoke_type_id = $request->integer('yoke_type_id');
            $technical_sheet->back_type_id = $request->integer('back_type_id');
            $technical_sheet->physical_sample = $request->integer('physical_sample');
            $technical_sheet->number_of_buttons = $request->integer('number_of_buttons');
            $technical_sheet->description = $request->input('description');
            $technical_sheet->observation = $request->input('observation');
            $technical_sheet->pattern_maker_id = $request->integer('pattern_maker_id');
            $technical_sheet->status = $request->input('status');

            $technical_sheet->save();
            $technical_sheet->auditSync('supplies', $request->input('supplies', []));

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

            $this->save($request->input('technical_sheet_details', []), $technical_sheet->id, $request->integer('wash_tone_id') ?: null);

            // Lo quitado de la ficha (ya validado): el subproceso con sus operaciones
            foreach ($request->input('removed_details', []) as $item) {
                $model = $item['model_type']::find($item['model_id']);
                $removed = collect([$model])->concat($model instanceof Subprocess ? $model->operations : []);

                TechnicalSheetDetail::where('technical_sheet_id', $technical_sheet->id)
                    ->where(fn ($query) => $removed->each(fn ($item) => $query->orWhere(fn ($own) => $own
                        ->where('model_type', $item::class)
                        ->where('model_id', $item->id))))
                    ->get()
                    ->each->delete();
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
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    private function save(array $technical_sheet_details, int $technical_sheet_id, ?int $wash_tone_id = null)
    {
        $keys = collect($technical_sheet_details)
            ->map(fn ($detail) => ($detail['model_type'] ?? '').':'.($detail['model_id'] ?? ''))
            ->unique()
            ->values();

        // Sin try/catch propio: si falla, el error llega al de store/update y la respuesta no dice «éxito»
        foreach ($technical_sheet_details as $technical_sheet_detail) {
            // Un subproceso automático que no aplica a la ficha (LAVADO 3 sin EFECTO TRAPO) no se guarda
            if ($this->inapplicableAutomatic($technical_sheet_detail['model_type'], $technical_sheet_detail['model_id'], $keys, $wash_tone_id)) {
                continue;
            }

            $stored = TechnicalSheetDetail::where('technical_sheet_id', $technical_sheet_id)
                ->where('model_type', $technical_sheet_detail['model_type'])
                ->where('model_id', $technical_sheet_detail['model_id'])
                ->first();

            // Lo Aprobado queda cerrado: si sigue Aprobado se conservan sus tablas y su tipificación aunque lleguen otras
            if ($stored?->status === TechnicalSheetDetail::APPROVED && $technical_sheet_detail['status'] === TechnicalSheetDetail::APPROVED) {
                continue;
            }

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

        // Los que ya estaban guardados y ahora no aplican
        TechnicalSheetDetail::where('technical_sheet_id', $technical_sheet_id)
            ->where('model_type', Subprocess::class)
            ->get()
            ->filter(fn (TechnicalSheetDetail $detail) => $this->inapplicableAutomatic($detail->model_type, $detail->model_id, $keys, $wash_tone_id))
            ->each->delete();
    }

    private function inapplicableAutomatic(string $model_type, $model_id, $keys, ?int $wash_tone_id): bool
    {
        if ($model_type !== Subprocess::class) {
            return false;
        }

        $subprocess = Subprocess::find($model_id);

        return $subprocess
            && ! ($subprocess->settings->in_technical_sheet ?? false)
            && ! app(ProductivityDetailService::class)->sheetSubprocessApplies($subprocess, $keys, $wash_tone_id);
    }

    private function resolveDynamicField($field, $value)
    {

        if (empty($field->model) || empty($value) || ! class_exists($field->model)) {
            return $value;
        }

        $model = $field->model;

        // También los desactivados: la ficha guarda el id y el registro pudo desactivarse después (p. ej. PESPUNTE)
        $record = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model), true)
            ? $model::withTrashed()->find($value)
            : $model::find($value);

        if (! $record) {
            return $value;
        }

        $config = collect($this->getModelTypes())
            ->firstWhere('model', $model);

        if (! $config) {
            return $record->id;
        }

        $option = $config['option'] ?? 'name';

        if (is_array($option)) {
            return collect($option)
                ->map(fn ($path) => data_get($record, $path))
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
                ! empty($settings->dinamic->values) &&
                ! empty($settings->dinamic->body)
            ) {

                $body = collect($settings->dinamic->body);

                $settings->dinamic->values = collect($settings->dinamic->values)
                    ->map(function ($row) use ($body) {
                        foreach (get_object_vars($row) as $field => $value) {
                            $definition = $body->firstWhere('field', $field);

                            if (! $definition) {
                                continue;
                            }

                            if (is_object($value)) {
                                $row->{$field} = $value->name ?? '-';

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
                ! empty($settings->static->values) &&
                ! empty($settings->static->body)
            ) {

                $body = collect($settings->static->body)
                    ->flatten(1);

                foreach (get_object_vars($settings->static->values) as $field => $value) {

                    $definition = $body->firstWhere('field', $field);

                    if (! $definition) {
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

    /**
     * Nombre de la ficha técnica en PDF: TS- + la referencia y el lote de la orden (TS-BL563082-A).
     */
    private function pdfName(TechnicalSheet $technical_sheet, ProductionOrder $production_order): string
    {
        return 'TS-'.($technical_sheet->product->code ?? '-').'-'.($production_order->cut ?? '-');
    }

    /**
     * @OA\Get(
     *     path="/technical_sheets/download_pdf/{uuid}",
     *     tags={"Technical Sheets"},
     *     summary="Pedir el PDF de la ficha técnica de un lote",
     *     description="Ruta con sesión. Genera el PDF de la ficha técnica del lote (orden de producción) con ese `uuid`, lo guarda unos minutos y **no devuelve el archivo** sino una dirección firmada y temporal (vence a los 5 minutos) que termina en el nombre `TS-<referencia>-<lote>.pdf`; esa dirección se abre con la ruta `GET /technical_sheets/pdf/{uuid}/{name}`. Ojo: el `uuid` es el de la **orden de producción**, no el de la ficha. El Request no exige permisos: basta con estar autenticado.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID de la orden de producción (lote) cuya ficha se imprime.", @OA\Schema(type="string", format="uuid", example="9b2f3c1e-7a44-4d0e-8f5a-2c61d0b7e913")),
     *     @OA\Response(
     *         response=200,
     *         description="Dirección firmada del PDF generado",
     *         @OA\JsonContent(ref="#/components/schemas/TechnicalSheetPdfLinkResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El `uuid` no corresponde a ninguna orden de producción.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"uuid": "Código UUID de la ficha tecníca", "production_order_id": "Identificador de la orden de producción"},
     *                 "errors": {"uuid": {"No está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/technical_sheets/pdf/{uuid}/{name}",
     *     tags={"Technical Sheets"},
     *     summary="Descargar el PDF de la ficha técnica de un lote (dirección firmada)",
     *     description="Sin token Bearer: la protege la firma de la URL (middleware `signed`), no la sesión. Es la dirección que devuelve `download_pdf` (con `expires` y `signature`) y la que lleva el código QR del PDF (sin `name` ni `expires`, ver la operación siguiente). Si hay un PDF guardado para ese `uuid` y `name` y no ha vencido, lo entrega tal cual; si no, lo genera de nuevo. Responde el archivo `application/pdf` (`inline`), no JSON. El `uuid` es el de la **orden de producción**.",
     *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID de la orden de producción (lote).", @OA\Schema(type="string", format="uuid", example="9b2f3c1e-7a44-4d0e-8f5a-2c61d0b7e913")),
     *     @OA\Parameter(name="name", in="path", required=true, description="Nombre del archivo que devolvió `download_pdf`. Solo sirve para encontrar el PDF guardado y como nombre del archivo.", @OA\Schema(type="string", example="TS-XJ1042-A.pdf")),
     *     @OA\Parameter(name="signature", in="query", required=true, description="Firma de la URL, generada por el servidor.", @OA\Schema(type="string", example="firma")),
     *     @OA\Parameter(name="expires", in="query", required=false, description="Marca de vencimiento (timestamp). Solo viene en las direcciones temporales de `download_pdf`.", @OA\Schema(type="integer", example=1795600000)),
     *     @OA\Response(
     *         response=200,
     *         description="PDF de la ficha técnica del lote",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(response=403, description="La firma de la URL no es válida o la dirección venció."),
     *     @OA\Response(
     *         response=422,
     *         description="El `uuid` no corresponde a ninguna orden de producción.",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/technical_sheets/pdf/{uuid}",
     *     tags={"Technical Sheets"},
     *     summary="Descargar el PDF de la ficha técnica de un lote (enlace del QR)",
     *     description="Igual que la operación anterior pero sin `name`: es la dirección firmada permanente que lleva el código QR impreso en el PDF. Como no hay nombre, no usa el PDF guardado: lo genera cada vez. Sin token Bearer, protegida por la firma. Responde el archivo `application/pdf`.",
     *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID de la orden de producción (lote).", @OA\Schema(type="string", format="uuid", example="9b2f3c1e-7a44-4d0e-8f5a-2c61d0b7e913")),
     *     @OA\Parameter(name="signature", in="query", required=true, description="Firma de la URL, generada por el servidor.", @OA\Schema(type="string", example="firma")),
     *     @OA\Response(
     *         response=200,
     *         description="PDF de la ficha técnica del lote",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(response=403, description="La firma de la URL no es válida."),
     *     @OA\Response(
     *         response=422,
     *         description="El `uuid` no corresponde a ninguna orden de producción.",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function pdf(TechnicalSheetExportPDFRequest $request, string $uuid)
    {
        try {
            // La dirección firmada que pidió el sistema entrega el PDF que ya se generó (ver PdfLinkService)
            if ($request->routeIs('technical_sheets.pdf') && ($cached = app(PdfLinkService::class)->cached('technical_sheets.pdf', $uuid, $request->route('name')))) {
                return $cached;
            }

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
                    'pattern_maker' => ['person'],
                    'supplies' => ['supply_type'],
                    'product' => ['trademark' => ['group', 'sizes'], 'subcategory' => ['category']],
                    'products' => ['trademark'],
                ],
                'production_order_details' => ['production_order_detail_quantities', 'model'],
                'fabric' => ['model'],
                'pieces',
                'rolls',
                'derivatives' => ['production_order' => ['technical_sheet' => ['product']]],
                'reassignments' => ['model' => ['technical_sheet' => ['product']]],
            ])->where('uuid', $uuid)->firstOrFail();
            
            $pdfUrl = URL::signedRoute(
                'technical_sheets.pdf',
                [
                    // La ficha técnica se descarga desde una orden de producción: el QR apunta a esa orden
                    'uuid' => $production_order->uuid,
                ]
            );

            $qr = app(QrCodeService::class)->generate($pdfUrl);

            $name = $this->pdfName($production_order->technical_sheet, $production_order);

            $production_order->technical_sheet->technical_sheet_details =
                $this->formatTechnicalSheetDetails(
                    $production_order->technical_sheet->technical_sheet_details
                );

            $processes = Process::with(['subprocesses_with_trashed' => ['operations_with_trashed']])->where('settings->in_technical_sheet', true)
                ->get();

            // Se imprime con Chrome, con el mismo diseño de la ficha de procesos
            $image = fn (string $path) => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($path)));
            $output = app(ChromePdfService::class)->render(
                view('pdf.TechnicalSheet.pdf', [
                    'technical_sheet' => $production_order->technical_sheet,
                    'production_order' => $production_order,
                    'processes' => $processes,
                    'logo' => $image('images/logo.png'),
                    'qr' => $qr,
                    'name' => $name,
                ])->render(),
                [
                    'footer' => view('pdf.TechnicalSheet.pdf', ['footer' => true, 'kanri' => $image('images/logo-kanri-dark.png')])->render(),
                    'margin_top' => 12,
                    'margin_bottom' => 22,
                    'margin_side' => 20,
                ]
            );

            $fileName = $name.'.pdf';

            // Desde el sistema (ruta con sesión) se guarda y se devuelve la dirección firmada que termina en el nombre
            if (! $request->routeIs('technical_sheets.pdf')) {
                return $this->successResponse(['url' => app(PdfLinkService::class)->store('technical_sheets.pdf', $uuid, $fileName, $output)]);
            }

            return response(
                $output,
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline;  filename="'.$fileName.'"',
                ]
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
     * Ficha de procesos en PDF: la ficha técnica sin orden de producción (sin lote, curva ni destinos), con sus procesos,
     * subprocesos y operaciones y, al final, la línea de trabajo por la que pasa la referencia.
     *
     * @OA\Get(
     *     path="/technical_sheets/download_process_pdf/{uuid}",
     *     tags={"Technical Sheets"},
     *     summary="Pedir el PDF de la ficha de procesos",
     *     description="Ruta con sesión. Genera la ficha de procesos de la ficha técnica con ese `uuid`: la ficha sin lote, curva ni destinos, con sus procesos, subprocesos y operaciones y, al final, la línea de trabajo por la que pasa la referencia. **No devuelve el archivo** sino una dirección firmada y temporal (vence a los 5 minutos) que termina en el nombre `WS-<referencia>.pdf`; esa dirección se abre con `GET /technical_sheets/process_pdf/{uuid}/{name}`. Aquí el `uuid` es el de la **ficha técnica**. El Request no exige permisos: basta con estar autenticado.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID de la ficha técnica (activa).", @OA\Schema(type="string", format="uuid", example="3f8c2a10-5b7d-4e6a-9c21-8d4e0f1a7b52")),
     *     @OA\Response(
     *         response=200,
     *         description="Dirección firmada del PDF generado",
     *         @OA\JsonContent(ref="#/components/schemas/TechnicalSheetPdfLinkResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El `uuid` no es válido o no corresponde a una ficha técnica activa.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"uuid": "Código UUID de la ficha técnica"},
     *                 "errors": {"uuid": {"No está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/technical_sheets/process_pdf/{uuid}/{name}",
     *     tags={"Technical Sheets"},
     *     summary="Descargar el PDF de la ficha de procesos (dirección firmada)",
     *     description="Sin token Bearer: la protege la firma de la URL (middleware `signed`). Es la dirección que devuelve `download_process_pdf` (con `expires` y `signature`). Si hay un PDF guardado para ese `uuid` y `name` y no ha vencido, lo entrega tal cual; si no, lo genera de nuevo. Responde el archivo `application/pdf` (`inline`), no JSON. El `uuid` es el de la **ficha técnica**.",
     *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID de la ficha técnica.", @OA\Schema(type="string", format="uuid", example="3f8c2a10-5b7d-4e6a-9c21-8d4e0f1a7b52")),
     *     @OA\Parameter(name="name", in="path", required=true, description="Nombre del archivo que devolvió `download_process_pdf`. Solo sirve para encontrar el PDF guardado y como nombre del archivo.", @OA\Schema(type="string", example="WS-XJ1042.pdf")),
     *     @OA\Parameter(name="signature", in="query", required=true, description="Firma de la URL, generada por el servidor.", @OA\Schema(type="string", example="firma")),
     *     @OA\Parameter(name="expires", in="query", required=false, description="Marca de vencimiento (timestamp). Solo viene en las direcciones temporales de `download_process_pdf`.", @OA\Schema(type="integer", example=1795600000)),
     *     @OA\Response(
     *         response=200,
     *         description="PDF de la ficha de procesos",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(response=403, description="La firma de la URL no es válida o la dirección venció."),
     *     @OA\Response(
     *         response=422,
     *         description="El `uuid` no corresponde a una ficha técnica activa.",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/technical_sheets/process_pdf/{uuid}",
     *     tags={"Technical Sheets"},
     *     summary="Descargar el PDF de la ficha de procesos (enlace del QR)",
     *     description="Igual que la operación anterior pero sin `name`: es la dirección firmada permanente que lleva el código QR de la ficha de procesos. Como no hay nombre, no usa el PDF guardado: lo genera cada vez. Sin token Bearer, protegida por la firma. Responde el archivo `application/pdf`.",
     *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID de la ficha técnica.", @OA\Schema(type="string", format="uuid", example="3f8c2a10-5b7d-4e6a-9c21-8d4e0f1a7b52")),
     *     @OA\Parameter(name="signature", in="query", required=true, description="Firma de la URL, generada por el servidor.", @OA\Schema(type="string", example="firma")),
     *     @OA\Response(
     *         response=200,
     *         description="PDF de la ficha de procesos",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(response=403, description="La firma de la URL no es válida."),
     *     @OA\Response(
     *         response=422,
     *         description="El `uuid` no corresponde a una ficha técnica activa.",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function process_pdf(TechnicalSheetProcessPdfRequest $request, string $uuid)
    {
        try {
            // La dirección firmada que pidió el sistema entrega el PDF que ya se generó (ver PdfLinkService)
            if ($request->routeIs('technical_sheets.process_pdf') && ($cached = app(PdfLinkService::class)->cached('technical_sheets.process_pdf', $uuid, $request->route('name')))) {
                return $cached;
            }

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
                'technical_sheet_details' => ['typology'],
                'pattern_maker' => ['person', 'position' => ['area']],
                'product' => ['trademark' => ['group'], 'subcategory' => ['category']],
                'products' => ['trademark'],
            ])->withCount('production_orders')->where('uuid', $uuid)->firstOrFail();

            $line =app(ProductivityDetailService::class)->sheetLine($technical_sheet);

            $technical_sheet->technical_sheet_details = $this->formatTechnicalSheetDetails($technical_sheet->technical_sheet_details);

            // La ficha de procesos (workflow sheet) se llama WS- + la referencia
            $name = 'WS-'.($technical_sheet->product->code ?? '-');

            // Se imprime con Chrome para que salga como el diseño (esquinas redondeadas, sombras, diagrama en SVG)
            $image = fn (string $path) => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($path)));
            $output = app(ChromePdfService::class)->render(
                view('pdf.ProcessSheet.pdf', [
                    'technical_sheet' => $technical_sheet,
                    'processes' => Process::with(['subprocesses_with_trashed' => ['operations_with_trashed']])->where('settings->in_technical_sheet', true)->get(),
                    'line' => $line,
                    'name' => $name,
                    'logo' => $image('images/logo.png'),
                    'qr' => app(QrCodeService::class)->generate(URL::signedRoute('technical_sheets.process_pdf', ['uuid' => $technical_sheet->uuid])),
                ])->render(),
                [
                    'footer' => view('pdf.ProcessSheet.pdf', ['footer' => true, 'kanri' => $image('images/logo-kanri-dark.png')])->render(),
                    'margin_top' => 12,
                    'margin_bottom' => 22,
                    'margin_side' => 20,
                ]
            );

            // Desde el sistema (ruta con sesión) se guarda y se devuelve la dirección firmada que termina en el nombre
            if (! $request->routeIs('technical_sheets.process_pdf')) {
                return $this->successResponse(['url' => app(PdfLinkService::class)->store('technical_sheets.process_pdf', $uuid, $name.'.pdf', $output)]);
            }

            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$name.'.pdf"',
            ]);
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
