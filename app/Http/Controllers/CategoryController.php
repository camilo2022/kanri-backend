<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categorization\Category\CategoryAllRequest;
use App\Http\Requests\Categorization\Category\CategoryDeleteRequest;
use App\Http\Requests\Categorization\Category\CategoryFindRequest;
use App\Http\Requests\Categorization\Category\CategoryRestoreRequest;
use App\Http\Requests\Categorization\Category\CategoryStoreRequest;
use App\Http\Requests\Categorization\Category\CategoryUpdateRequest;
use App\Http\Resources\Categorization\Category\CategoryCollection;
use App\Http\Resources\Categorization\Category\CategoryResource;
use App\Models\Category;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="CategorySubcategory",
 *     description="Subcategoría asociada a la categoría, con el pivote de `model_has_subitems`.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Subitem"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="pivot",
 *                 type="object",
 *                 @OA\Property(property="model_type", type="string", example="App\Models\Subcategory"),
 *                 @OA\Property(property="subitem_id", type="integer", description="Id de la categoría.", example=64),
 *                 @OA\Property(property="model_id", type="integer", description="Id de la subcategoría.", example=211)
 *             )
 *         )
 *     },
 *     example={"id": 211, "item_id": 12, "name": "JEAN CARGO URBANO", "description": "JEAN CARGO CON BOLSILLOS LATERALES", "settings": {}, "created_at": "2026-05-14T12:10:00.000000Z", "updated_at": "2026-05-14T12:10:00.000000Z", "deleted_at": null, "pivot": {"model_type": "App\Models\Subcategory", "subitem_id": 64, "model_id": 211}}
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=64),
 *     @OA\Property(property="item_id", type="integer", example=11),
 *     @OA\Property(property="name", type="string", example="URBANO"),
 *     @OA\Property(property="description", type="string", example="LÍNEA DE JEANS DE USO DIARIO CON CORTES MODERNOS"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 12:05:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 12:05:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=11),
 *         @OA\Property(property="name", type="string", example="Categorías"),
 *         @OA\Property(property="description", type="string", example="Listado de categorías."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(property="subcategories", type="array", description="Subcategorías de la categoría.", @OA\Items(ref="#/components/schemas/CategorySubcategory"))
 * )
 *
 * @OA\Schema(
 *     schema="CategoryResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="category", ref="#/components/schemas/Category")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="CategoryListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/Category")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="CategoryRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre las categorías.", example="URBANO"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="LÍNEA DE JEANS DE USO DIARIO CON CORTES MODERNOS")
 * )
 */
class CategoryController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/categorization/categories/all",
     *     tags={"Categorization › Categories"},
     *     summary="Listar las categorías",
     *     description="Cada categoría trae sus subcategorías. En esta lista no viene `settings`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="subcategory_id",
     *         in="query",
     *         required=false,
     *         description="Solo las categorías que contienen esta subcategoría.",
     *         @OA\Schema(type="integer", example=211)
     *     ),
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
     *         description="Lista de categorías cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos", "subcategory_id": "Identificador de la Subcategoria"},
     *                 "errors": {"subcategory_id": {"Debe ser un valor numérico."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(CategoryAllRequest $request)
    {
        try {
            $categories = Category::with(['item', 'subcategories'])
                ->when($request->filled('subcategory_id'), function ($query) use ($request) {
                    return $query->whereHas('subcategories', fn($r) => $r->where('id', $request->subcategory_id));
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

            $categories = $categories->paginate($request->integer('per_page', $categories->count()));

            return $this->successResponse(
                new CategoryCollection($categories),
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
     *     path="/categorization/categories/find/{id}",
     *     tags={"Categorization › Categories"},
     *     summary="Obtener una categoría",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(CategoryFindRequest $request, $id)
    {
        try {
            $category = Category::with(['item', 'subcategories'])
                ->findOrFail($id);

            return $this->successResponse(
                new CategoryResource($category),
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
     *     path="/categorization/categories/store",
     *     tags={"Categorization › Categories"},
     *     summary="Crear una categoría",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CategoryRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Categoría creada correctamente. `subcategories` llega vacío.",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Se permite mínimo 4 caracteres."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(CategoryStoreRequest $request)
    {
        try {
            $category = new Category();
            $category->name = $request->input('name');
            $category->description = $request->input('description');
            $category->save();

            $category->load(['item', 'subcategories']);

            return $this->successResponse(
                new CategoryResource($category),
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
     *     path="/categorization/categories/update/{id}",
     *     tags={"Categorization › Categories"},
     *     summary="Editar una categoría",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/CategoryRequest",
     *             example={"name": "VINTAGE", "description": "LÍNEA DE JEANS CON LAVADOS Y DESGASTES DE ESTILO RETRO"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o categoría no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del area", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(CategoryUpdateRequest $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->name = $request->input('name');
            $category->description = $request->input('description');
            $category->save();

            $category->load(['item', 'subcategories']);

            return $this->successResponse(
                new CategoryResource($category),
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
     * @OA\Delete(
     *     path="/categorization/categories/delete/{id}",
     *     tags={"Categorization › Categories"},
     *     summary="Desactivar una categoría",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(CategoryDeleteRequest $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            $category->load(['item', 'subcategories']);

            return $this->successResponse(
                new CategoryResource($category),
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
     * @OA\Patch(
     *     path="/categorization/categories/restore/{id}",
     *     tags={"Categorization › Categories"},
     *     summary="Restaurar una categoría desactivada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(CategoryRestoreRequest $request, $id)
    {
        try {
            $category = Category::withTrashed()->findOrFail($id);
            $category->restore();

            $category->load(['item', 'subcategories']);

            return $this->successResponse(
                new CategoryResource($category),
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
