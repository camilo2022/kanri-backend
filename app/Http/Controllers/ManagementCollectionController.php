<?php

namespace App\Http\Controllers;

use App\Http\Requests\Management\Collection\CollectionFindRequest;
use App\Http\Requests\Management\TechnicalSheet\TechnicalSheetSaveRequest;
use App\Http\Resources\Management\Collection\CollectionResource;
use App\Http\Resources\Management\Collection\TechnicalSheetResource;
use App\Models\Collection;
use App\Models\Product;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Schema(
 *     schema="ManagementTechnicalSheetDetail",
 *     type="object",
 *     description="Estado de la ficha técnica en un proceso (una fila por proceso).",
 *     @OA\Property(property="id", type="integer", example=7731),
 *     @OA\Property(property="technical_sheet_id", type="integer", example=905),
 *     @OA\Property(property="model_type", type="string", example="App\Models\Process"),
 *     @OA\Property(property="model_id", type="integer", description="Id del proceso.", example=24),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado"}, example="En revision"),
 *     @OA\Property(property="settings", type="object", additionalProperties=true, description="Formulario del proceso (secciones `static` y `dinamic`) tal como lo envía el frontend. `{}` si no se envía."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T21:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T21:00:00.000000Z"),
 *     @OA\Property(property="typology", type="array", description="Tipología del proceso (0 o 1 elemento).", @OA\Items(ref="#/components/schemas/Subitem"))
 * )
 *
 * @OA\Schema(
 *     schema="ManagementTechnicalSheet",
 *     type="object",
 *     description="Ficha técnica de la gestión de colecciones. En `find` viene como modelo de Eloquent (fechas ISO, incluye `consecutive`, `model_type`, `model_id` y `pattern_maker_id`); en `save` viene del Resource (fechas `Y-m-d H:i:s`, sin esos cuatro campos).",
 *     @OA\Property(property="id", type="integer", example=905),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="3f6d2a1c-8b4e-4c7a-9e21-6a5b0d4c2f18"),
 *     @OA\Property(property="code", type="string", nullable=true, description="Código de la ficha. Obligatorio salvo en estado Pendiente.", example=null),
 *     @OA\Property(property="consecutive", type="string", description="Generado por el procedimiento `generate_technical_sheet_consecutive()` al crear. Solo en `find`.", example="TS261022001"),
 *     @OA\Property(property="date", type="string", nullable=true, example=null),
 *     @OA\Property(property="product_id", type="integer", example=1460),
 *     @OA\Property(property="collection_id", type="integer", example=118),
 *     @OA\Property(property="garment_type_id", type="integer", example=55),
 *     @OA\Property(property="subgroup_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="wash_tone_id", type="integer", example=203),
 *     @OA\Property(property="color_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="waistband_type_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="measure_of_waistband", type="string", nullable=true, example=null),
 *     @OA\Property(property="boot_type_id", type="integer", example=47),
 *     @OA\Property(property="yoke_type_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="back_type_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="physical_sample", nullable=true, example=null),
 *     @OA\Property(property="number_of_buttons", type="integer", nullable=true, example=null),
 *     @OA\Property(property="description", type="string", nullable=true, example=null),
 *     @OA\Property(property="observation", type="string", nullable=true, example="LAVADO MÁS CLARO QUE LA MUESTRA ANTERIOR"),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado", "Cancelado"}, example="En revision"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 16:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 16:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="product",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1460),
 *         @OA\Property(property="code", type="string", description="Referencia del producto.", example="DC-000245"),
 *         @OA\Property(property="trademark_id", type="integer", example=21),
 *         @OA\Property(property="subcategory_id", type="integer", example=112),
 *         @OA\Property(property="trademark", ref="#/components/schemas/Subitem"),
 *         @OA\Property(property="subcategory", description="Subcategoría con su `category` (lista con la categoría).", allOf={@OA\Schema(ref="#/components/schemas/Subitem")})
 *     ),
 *     @OA\Property(property="supplies", type="array", description="Insumos de la ficha (con `pivot` y `supply_type`).", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="technical_sheet_details", type="array", @OA\Items(ref="#/components/schemas/ManagementTechnicalSheetDetail")),
 *     @OA\Property(property="collection", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="garment_type", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="wash_tone", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="boot_type", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="photo_d", nullable=true, description="Foto delantera.", allOf={@OA\Schema(ref="#/components/schemas/File")}),
 *     @OA\Property(property="photo_t", nullable=true, description="Foto trasera.", allOf={@OA\Schema(ref="#/components/schemas/File")})
 * )
 *
 * @OA\Schema(
 *     schema="ManagementCollectionResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="collection",
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/Collection"),
 *                 @OA\Schema(@OA\Property(property="technical_sheets", type="array", @OA\Items(ref="#/components/schemas/ManagementTechnicalSheet")))
 *             }
 *         )
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ManagementTechnicalSheetResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="technical_sheet", ref="#/components/schemas/ManagementTechnicalSheet")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ManagementTechnicalSheetSaveRequest",
 *     type="object",
 *     required={"collection_id", "garment_type_id", "wash_tone_id", "boot_type_id", "status", "product", "technical_sheet_details", "supplies", "photo_d", "photo_t"},
 *     @OA\Property(property="id", type="integer", description="Ficha a actualizar. Si no se envía o no existe, se crea una nueva con su consecutivo.", example=905),
 *     @OA\Property(property="code", type="string", nullable=true, description="Código de la ficha. Obligatorio salvo con `status` Pendiente; único.", example=null),
 *     @OA\Property(property="collection_id", type="integer", example=118),
 *     @OA\Property(property="garment_type_id", type="integer", example=55),
 *     @OA\Property(property="wash_tone_id", type="integer", example=203),
 *     @OA\Property(property="boot_type_id", type="integer", example=47),
 *     @OA\Property(property="observation", type="string", maxLength=255, example="LAVADO MÁS CLARO QUE LA MUESTRA ANTERIOR"),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado", "Cancelado"}, description="Estado general de la ficha (lo calcula el frontend a partir de los procesos).", example="En revision"),
 *     @OA\Property(
 *         property="product",
 *         type="object",
 *         required={"code", "trademark_id", "subcategory_id"},
 *         description="Se crea o actualiza según `product.id`.",
 *         @OA\Property(property="id", type="integer", example=1460),
 *         @OA\Property(property="code", type="string", description="Único. Con `status` Pendiente debe ser `<código de la marca>-` y 6 números (por ejemplo `DC-000245`); en los demás estados, debe cumplir las validaciones configuradas en la marca.", example="DC-000245"),
 *         @OA\Property(property="trademark_id", type="integer", example=21),
 *         @OA\Property(property="subcategory_id", type="integer", example=112)
 *     ),
 *     @OA\Property(
 *         property="technical_sheet_details",
 *         type="array",
 *         description="Estado por proceso. Se crea o actualiza por (ficha, `model_type`, `model_id`).",
 *         @OA\Items(
 *             type="object",
 *             required={"model_type", "model_id", "status"},
 *             @OA\Property(property="id", type="integer", example=7731),
 *             @OA\Property(property="model_type", type="string", example="App\Models\Process"),
 *             @OA\Property(property="model_id", type="integer", description="Id del proceso.", example=24),
 *             @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado"}, example="En revision"),
 *             @OA\Property(property="typology", type="integer", nullable=true, description="Id de tipología activa.", example=310),
 *             @OA\Property(property="settings", type="object", additionalProperties=true, description="Formulario del proceso. Opcional.")
 *         )
 *     ),
 *     @OA\Property(property="supplies", type="array", description="Ids de insumos. Debe haber al menos uno de cada tipo de insumo marcado para ficha técnica (hoy TELA e HILO).", @OA\Items(type="integer"), example={7020, 7055}),
 *     @OA\Property(property="photo_d", type="object", description="Foto delantera. Para subirla se usa `multipart/form-data` con `photo_d[file]`.", @OA\Property(property="photo_type_id", type="integer", example=3), @OA\Property(property="photo_subtype_id", type="integer", example=9)),
 *     @OA\Property(property="photo_t", type="object", description="Foto trasera. Igual que `photo_d`.", @OA\Property(property="photo_type_id", type="integer", example=3), @OA\Property(property="photo_subtype_id", type="integer", example=9))
 * )
 */
class ManagementCollectionController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/management/collections/find/{id}",
     *     tags={"Management › Collections"},
     *     summary="Obtener una colección con sus fichas técnicas para gestionarlas",
     *     description="Carga la colección con todas sus fichas técnicas: producto (marca, subcategoría y categoría), insumos con su tipo, estado por proceso con tipología, tipo de prenda, tono de lavado, tipo de bota y fotos delantera y trasera.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Colección cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ManagementCollectionResponse")
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
            $collection = Collection::with(['technical_sheets' => ['collection', 'product' => ['trademark', 'subcategory' => ['category']], 'supplies' => ['supply_type'], 'technical_sheet_details' => ['typology'], 'garment_type', 'wash_tone', 'boot_type', 'photo_d', 'photo_t']])
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
     *     path="/management/collections/save",
     *     tags={"Management › Collections"},
     *     summary="Crear o actualizar una ficha técnica de la colección",
     *     description="Guarda una ficha completa en un solo envío: producto (crea o actualiza), ficha (si es nueva, le asigna consecutivo), estado por proceso con tipología, fotos y insumos. El frontend envía una petición por cada ficha modificada. Las fotos deben llamarse `<product.code>-D` (delantera) y `<product.code>-T` (trasera), por ejemplo `DC-000245-D.jpg`, y pesar máximo 2 MB.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 description="Formato que usa el frontend: los objetos y listas van con corchetes.",
     *                 required={"collection_id", "garment_type_id", "wash_tone_id", "boot_type_id", "status", "product[code]", "product[trademark_id]", "product[subcategory_id]", "technical_sheet_details[0][model_type]", "technical_sheet_details[0][model_id]", "technical_sheet_details[0][status]", "supplies[0]", "photo_d[photo_type_id]", "photo_t[photo_type_id]"},
     *                 @OA\Property(property="id", type="integer", example=905),
     *                 @OA\Property(property="code", type="string", example=""),
     *                 @OA\Property(property="collection_id", type="integer", example=118),
     *                 @OA\Property(property="garment_type_id", type="integer", example=55),
     *                 @OA\Property(property="wash_tone_id", type="integer", example=203),
     *                 @OA\Property(property="boot_type_id", type="integer", example=47),
     *                 @OA\Property(property="observation", type="string", example="LAVADO MÁS CLARO QUE LA MUESTRA ANTERIOR"),
     *                 @OA\Property(property="status", type="string", enum={"Pendiente", "En revision", "Aprobado", "Cancelado"}, example="Pendiente"),
     *                 @OA\Property(property="product[id]", type="integer", example=1460),
     *                 @OA\Property(property="product[code]", type="string", example="DC-000245"),
     *                 @OA\Property(property="product[trademark_id]", type="integer", example=21),
     *                 @OA\Property(property="product[subcategory_id]", type="integer", example=112),
     *                 @OA\Property(property="technical_sheet_details[0][model_type]", type="string", example="App\Models\Process"),
     *                 @OA\Property(property="technical_sheet_details[0][model_id]", type="integer", example=24),
     *                 @OA\Property(property="technical_sheet_details[0][status]", type="string", enum={"Pendiente", "En revision", "Aprobado"}, example="Pendiente"),
     *                 @OA\Property(property="technical_sheet_details[0][typology]", type="integer", example=310),
     *                 @OA\Property(property="supplies[0]", type="integer", description="Insumo de tela.", example=7020),
     *                 @OA\Property(property="supplies[1]", type="integer", description="Insumo de hilo.", example=7055),
     *                 @OA\Property(property="photo_d[file]", type="string", format="binary", description="Debe llamarse `<product.code>-D` (por ejemplo `DC-000245-D.jpg`)."),
     *                 @OA\Property(property="photo_d[photo_type_id]", type="integer", example=3),
     *                 @OA\Property(property="photo_d[photo_subtype_id]", type="integer", example=9),
     *                 @OA\Property(property="photo_t[file]", type="string", format="binary", description="Debe llamarse `<product.code>-T`."),
     *                 @OA\Property(property="photo_t[photo_type_id]", type="integer", example=3),
     *                 @OA\Property(property="photo_t[photo_subtype_id]", type="integer", example=9)
     *             )
     *         ),
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ManagementTechnicalSheetSaveRequest",
     *             example={"id": 905, "code": null, "collection_id": 118, "garment_type_id": 55, "wash_tone_id": 203, "boot_type_id": 47, "observation": "LAVADO MÁS CLARO QUE LA MUESTRA ANTERIOR", "status": "En revision", "product": {"id": 1460, "code": "DC-000245", "trademark_id": 21, "subcategory_id": 112}, "technical_sheet_details": {{"model_type": "App\Models\Process", "model_id": 24, "status": "Aprobado", "typology": 310}, {"model_type": "App\Models\Process", "model_id": 25, "status": "En revision"}}, "supplies": {7020, 7055}, "photo_d": {"photo_type_id": 3, "photo_subtype_id": 9}, "photo_t": {"photo_type_id": 3, "photo_subtype_id": 9}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ficha técnica guardada correctamente (también al crearla: responde 200, no 201).",
     *         @OA\JsonContent(ref="#/components/schemas/ManagementTechnicalSheetResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos: código de producto que no cumple el formato de la marca, falta un insumo de un tipo obligatorio, nombre de foto incorrecto, etc.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"product.code": "Referencia del producto", "photo_d": "Foto delantera", "photo_t": "Foto trasera"},
     *                 "errors": {"product.code": {"El código debe tener el formato DC- seguido de 6 números."}, "supply.88": {"Debe seleccionar un insumo"}, "photo_d.file": {"El nombre del archivo debe ser DC-000245-D."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor. Este endpoint no captura excepciones: responde con el formato por defecto de Laravel, no con `{error: {...}}`, y lo guardado antes del fallo no se revierte (ver pendientes).",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Server Error"))
     *     )
     * )
     */
    public function save(TechnicalSheetSaveRequest $request)
    {
        $product = Product::updateOrCreate(
            ['id' => $request->integer('product.id')],
            [
                'code' => $request->input('product.code'),
                'subcategory_id' => $request->integer('product.subcategory_id'),
                'trademark_id' => $request->integer('product.trademark_id'),
            ]
        );

        $technical_sheet = TechnicalSheet::find($request->integer('id'));

        if (! $technical_sheet) {
            $result = DB::select('CALL generate_technical_sheet_consecutive()');

            $consecutive = $result[0]->consecutive;

            $technical_sheet = TechnicalSheet::create([
                'code' => $request->input('code'),
                'product_id' => $product->id,
                'collection_id' => $request->integer('collection_id'),
                'garment_type_id' => $request->integer('garment_type_id'),
                'wash_tone_id' => $request->integer('wash_tone_id'),
                'boot_type_id' => $request->integer('boot_type_id'),
                'observation' => $request->input('observation'),
                'consecutive' => $consecutive,
                'status' => $request->input('status'),
            ]);
        } else {
            $technical_sheet->update([
                'code' => $request->input('code'),
                'product_id' => $product->id,
                'collection_id' => $request->integer('collection_id'),
                'garment_type_id' => $request->integer('garment_type_id'),
                'wash_tone_id' => $request->integer('wash_tone_id'),
                'boot_type_id' => $request->integer('boot_type_id'),
                'observation' => $request->input('observation'),
                'status' => $request->input('status'),
            ]);
        }

        foreach ($request->input('technical_sheet_details', []) as $technical_sheet_detail) {
            $detail = TechnicalSheetDetail::updateOrCreate(
                [
                    'technical_sheet_id' => $technical_sheet->id,
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

        $technical_sheet->auditSync('supplies', $request->input('supplies', []));

        $technical_sheet->load(['collection', 'product' => ['trademark', 'subcategory' => ['category']], 'supplies' => ['supply_type'], 'technical_sheet_details' => ['typology'], 'garment_type', 'wash_tone', 'boot_type', 'photo_d', 'photo_t']);

        return $this->successResponse(
            new TechnicalSheetResource($technical_sheet),
            $this->getMessage('Success'),
            200
        );
    }
}
