<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\ProductAllRequest;
use App\Http\Requests\Product\ProductFindRequest;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Endpoints para gestionar productos"
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     description="Referencia de producto (el código sin lote, por ejemplo LM4521). Fechas en `Y-m-d H:i:s`.",
 *     @OA\Property(property="id", type="integer", example=1520),
 *     @OA\Property(property="code", type="string", description="Código de la referencia. Único.", example="LM4521"),
 *     @OA\Property(property="trademark_id", type="integer", example=99),
 *     @OA\Property(property="subcategory_id", type="integer", example=211),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 10:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 10:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="trademark", ref="#/components/schemas/Trademark"),
 *     @OA\Property(property="subcategory", allOf={
 *         @OA\Schema(ref="#/components/schemas/Subcategory"),
 *         @OA\Schema(@OA\Property(property="category", type="array", description="Categoría de la subcategoría.", @OA\Items(ref="#/components/schemas/Subitem")))
 *     })
 * )
 *
 * @OA\Schema(
 *     schema="ProductListItem",
 *     type="object",
 *     description="Producto como lo entrega `ProductCollection` (all). Trae la ficha técnica en `technical_sheet`: la propia o, si no tiene, la primera de la que es producto STARA.",
 *     @OA\Property(property="id", type="integer", example=1520),
 *     @OA\Property(property="has_audits", type="boolean", description="Si el producto tiene historial de auditoría.", example=true),
 *     @OA\Property(property="code", type="string", example="LM4521"),
 *     @OA\Property(property="trademark_id", type="integer", example=99),
 *     @OA\Property(property="subcategory_id", type="integer", example=211),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 10:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 10:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="trademark", ref="#/components/schemas/Trademark"),
 *     @OA\Property(property="subcategory", type="object", additionalProperties=true, description="Subcategoría con su categoría en `category`."),
 *     @OA\Property(property="technical_sheet", type="object", nullable=true, additionalProperties=true, description="Ficha técnica con sus catálogos (colección, subgrupo, color, tipos de pretina, prenda, lavado, bota, cotilla y trasero), detalles por proceso, fotos, insumos y órdenes de producción (cada una con `in_traceability`). `null` si el producto aún no tiene ficha."),
 *     @OA\Property(property="original", type="boolean", description="`true` si el producto no es STARA (no está ligado como STARA a la ficha de otra referencia).", example=true),
 *     @OA\Property(property="technical_sheet_has_audits", type="boolean", description="Si la ficha técnica tiene historial de auditoría.", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProductResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="product", ref="#/components/schemas/Product"),
 *         @OA\Property(property="status_technical_sheet", type="array", description="Estados posibles de una ficha técnica.", @OA\Items(type="string"), example={"Pendiente", "En revision", "Aprobado", "Cancelado"}),
 *         @OA\Property(property="status_technical_sheet_detail", type="array", description="Estados posibles del detalle de una ficha (proceso, subproceso u operación).", @OA\Items(type="string"), example={"Pendiente", "En revision", "Aprobado"}),
 *         @OA\Property(property="model_types", type="object", additionalProperties=true, description="Catálogos que usan los formularios dinámicos (etiqueta, modelo, URL y campo de cada uno).")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProductListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="products", type="array", @OA\Items(ref="#/components/schemas/ProductListItem")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
 *         @OA\Property(property="status_technical_sheet", type="array", description="Estados posibles de una ficha técnica.", @OA\Items(type="string"), example={"Pendiente", "En revision", "Aprobado", "Cancelado"})
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProductUpdateResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="product",
 *             type="object",
 *             description="Modelo tal cual lo serializa Eloquent (sin relaciones y con fechas ISO).",
 *             @OA\Property(property="id", type="integer", example=1520),
 *             @OA\Property(property="code", type="string", example="LM4521"),
 *             @OA\Property(property="trademark_id", type="integer", example=99),
 *             @OA\Property(property="subcategory_id", type="integer", example=211),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T15:00:00.000000Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-20T15:00:00.000000Z"),
 *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *         )
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProductStoreRequest",
 *     type="object",
 *     required={"code", "trademark_id", "subcategory_id"},
 *     @OA\Property(property="code", type="string", description="Único. Debe cumplir alguna de las reglas (`settings.validations`) de la marca; si no cumple ninguna, el error lista los mensajes de esas reglas.", example="LM4521"),
 *     @OA\Property(property="trademark_id", type="integer", description="Debe existir como marca. Si `reference_relation` es `true`, se ignora y se usa la marca STARA DENIM.", example=99),
 *     @OA\Property(property="subcategory_id", type="integer", description="Debe existir como subcategoría.", example=211),
 *     @OA\Property(property="reference_relation", type="boolean", description="`true`: el producto se crea como producto STARA y se liga a la ficha `technical_sheet_id`.", example=false),
 *     @OA\Property(property="technical_sheet_id", type="integer", nullable=true, description="Ficha técnica a la que se liga el producto STARA (solo se usa con `reference_relation` `true`). Debe existir.", example=905)
 * )
 *
 * @OA\Schema(
 *     schema="ProductUpdateRequest",
 *     type="object",
 *     required={"code", "trademark_id", "subcategory_id"},
 *     description="Al editar no se valida el código contra las reglas de la marca (solo que sea único) ni se acepta `reference_relation`.",
 *     @OA\Property(property="code", type="string", description="Único entre los demás productos.", example="LM4521"),
 *     @OA\Property(property="trademark_id", type="integer", description="Debe existir como marca.", example=99),
 *     @OA\Property(property="subcategory_id", type="integer", description="Debe existir como subcategoría.", example=211)
 * )
 */
class ProductController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/products/all",
     *     tags={"Products"},
     *     summary="Listar los productos",
     *     description="Lista paginada de referencias con su marca (con grupo y tallas), subcategoría (con categoría) y la ficha técnica. Si el producto es STARA, `technical_sheet` es la primera ficha a la que está ligado, y `original` es `false`. La búsqueda separa el texto en palabras, ignora guiones y busca cada una en el código, la marca, el grupo, la subcategoría, la categoría y en datos de la ficha (código, consecutivo, estado, colección, tipo de prenda, tono de lavado y tipo de bota). Sin `per_page` devuelve todos en una sola página.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`. Además de las listadas se acepta `technical_sheet_detail_{id}` (estado de un proceso en la ficha; `{id}` es el identificador del proceso).",
     *         @OA\Schema(type="string", example="code", enum={"id", "code", "created_at", "updated_at", "deleted_at", "trademark", "group", "category", "subcategory", "technical_sheet_code", "technical_sheet_consecutive", "technical_sheet_collection", "technical_sheet_garment_type", "technical_sheet_wash_tone", "technical_sheet_boot_type", "technical_sheet_status"})
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(ref="#/components/parameters/WithTrashedOptional"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de productos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"per_page": "Registros por página.", "dir": "Sentido del orden."}, "errors": {"dir": {"El campo Sentido del orden. seleccionado no es válido."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(ProductAllRequest $request)
    {
        try {
            $products = Product::with([
                'subcategory' => ['category'],
                'trademark' => ['group', 'sizes'],
                'technical_sheet' => ['model', 'collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type', 'technical_sheet_details', 'photo_d', 'photo_t', 'supplies', 'production_orders' => ['fabric', 'color', 'production_order_details' => ['model', 'production_order_detail_quantities']]],
                'technical_sheets' => ['collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type', 'technical_sheet_details', 'photo_d', 'photo_t', 'supplies', 'production_orders' => ['fabric', 'color', 'production_order_details' => ['model', 'production_order_detail_quantities']]]
            ])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $products = $products->paginate($request->integer('per_page', $products->count()));

            return $this->successResponse(
                new ProductCollection($products),
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
     * @OA\Get(
     *     path="/products/find/{id}",
     *     tags={"Products"},
     *     summary="Consultar un producto",
     *     description="Devuelve el producto con su marca (con grupo y tallas) y su subcategoría (con categoría), más los estados de ficha y los catálogos para formularios. A diferencia de `all`, no trae la ficha técnica. También consulta productos desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Producto cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El producto no existe.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"id": "Identificador del producto"}, "errors": {"id": {"No hay ningún registro."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(ProductFindRequest $request, $id)
    {
        try {
            $product = Product::with(['trademark' => ['group', 'sizes'], 'subcategory' => ['category']])
                ->findOrFail($id);

            return $this->successResponse(
                new ProductResource($product),
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
     *     path="/products/store",
     *     tags={"Products"},
     *     summary="Crear un producto",
     *     description="Crea la referencia con su marca y subcategoría. El código debe cumplir alguna de las reglas de la marca. Con `reference_relation` en `true` el producto se crea como STARA: la marca que se envía se reemplaza por STARA DENIM y se liga a la ficha `technical_sheet_id` (la relación queda en el historial de auditoría).",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProductStoreRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Producto creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos, código repetido o que no cumple las reglas de la marca.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"code": "Código de la Referencia del Producto", "trademark_id": "Identificador de la marca", "subcategory_id": "Identificador de la subcategoría"}, "errors": {"code": {"No cumple con ninguna de las validaciones: Debe iniciar con dos letras y terminar con cuatro números."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(ProductStoreRequest $request)
    {
        try {
            $product = new Product();

            $product->code = $request->input('code');
            $product->trademark_id = $request->input('trademark_id');
            $product->subcategory_id = $request->input('subcategory_id');

            $product->save();

            if (!empty($request->boolean('reference_relation'))) {
                $product->auditAttach('technical_sheets', $request->input('technical_sheet_id'));
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
     *     path="/products/update/{id}",
     *     tags={"Products"},
     *     summary="Editar un producto",
     *     description="Cambia el código, la marca y la subcategoría. Responde el modelo sin relaciones (`data.product`), distinto de `find` y `store`, que responden con el Resource completo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProductUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductUpdateResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos, producto no registrado o código repetido.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"id": "Identificador de la linea"}, "errors": {"code": {"El valor del campo code ya está en uso."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(ProductUpdateRequest $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->code = $request->input('code');
            $product->trademark_id = $request->input('trademark_id');
            $product->subcategory_id = $request->input('subcategory_id');

            $product->save();


            return $this->successResponse(
                [
                    'product' => $product
                ],
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
}
