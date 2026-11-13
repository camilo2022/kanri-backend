<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categorization\Subcategory\SubcategoryAllRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryDeleteRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryFindRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryRestoreRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryStoreRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryUpdateRequest;
use App\Http\Resources\Categorization\Subcategory\SubcategoryCollection;
use App\Http\Resources\Categorization\Subcategory\SubcategoryResource;
use App\Models\Subcategory;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="Subcategory",
 *     type="object",
 *     description="Subcategoría de producto (JEAN SKINNY, FALDA MIDI…). Se asocia a una o varias categorías por `model_has_subitems`.",
 *     @OA\Property(property="id", type="integer", example=211),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=12),
 *     @OA\Property(property="name", type="string", example="JEAN MOM FIT"),
 *     @OA\Property(property="description", type="string", example="JEAN DE TIRO ALTO Y PIERNA HOLGADA"),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 12:20:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 12:20:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=12),
 *         @OA\Property(property="name", type="string", example="Subcategorías"),
 *         @OA\Property(property="description", type="string", example="Listado de subcategorías."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SubcategoryResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="subcategory", allOf={
 *         @OA\Schema(ref="#/components/schemas/Subcategory"),
 *         @OA\Schema(@OA\Property(property="categories", nullable=true, example=null, description="Siempre `null`: el Resource lee `categories`, pero la relación se llama `category` (ver pendientes)."))
 *     })),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SubcategoryListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="subcategories", type="array", @OA\Items(allOf={
 *             @OA\Schema(ref="#/components/schemas/Subcategory"),
 *             @OA\Schema(@OA\Property(property="category", type="array", description="Categorías de la subcategoría (con su pivote).", @OA\Items(ref="#/components/schemas/Subitem")))
 *         })),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SubcategoryRequest",
 *     type="object",
 *     required={"category_id", "name", "description"},
 *     @OA\Property(property="category_id", type="integer", description="Categoría a la que se asocia. Solo se usa al crear y **no se valida** (ver pendientes).", example=64),
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre las subcategorías.", example="JEAN MOM FIT"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="JEAN DE TIRO ALTO Y PIERNA HOLGADA")
 * )
 */
class SubcategoryController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/categorization/categories/subcategories/all/{category_id}",
     *     tags={"Categorization › Subcategories"},
     *     summary="Listar las subcategorías de una categoría",
     *     description="Cada subcategoría trae `item` y sus categorías en `category`; no trae `item_id` ni `settings`. También la pueden consultar quienes ven categorías o configuran procesos, subprocesos u operaciones.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="path",
     *         required=true,
     *         description="Categoría. No se valida: un id que no existe devuelve la lista vacía.",
     *         @OA\Schema(type="integer", example=64)
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
     *         description="Lista de subcategorías cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubcategoryListResponse")
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
    public function all(SubcategoryAllRequest $request, $category_id)
    {
        try {
            $subcategories = Subcategory::with(['item', 'category'])
                ->whereHas('category', fn($r) => $r->where('id', $category_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $subcategories = $subcategories->paginate($request->integer('per_page', $subcategories->count()));

            return $this->successResponse(
                new SubcategoryCollection($subcategories),
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
     *     path="/categorization/categories/subcategories/find/{id}",
     *     tags={"Categorization › Subcategories"},
     *     summary="Obtener una subcategoría",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subcategoría cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubcategoryResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(SubcategoryFindRequest $request, $id)
    {
        try {
            $subcategory = Subcategory::with(['category'])
                ->findOrFail($id);

            return $this->successResponse(
                new SubcategoryResource($subcategory),
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
     *     path="/categorization/categories/subcategories/store",
     *     tags={"Categorization › Subcategories"},
     *     summary="Crear una subcategoría",
     *     description="Crea la subcategoría y la asocia a la categoría `category_id`.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SubcategoryRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Subcategoría creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubcategoryResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(SubcategoryStoreRequest $request)
    {
        try {
            $subcategory = new Subcategory();
            $subcategory->name = $request->input('name');
            $subcategory->description = $request->input('description');
            $subcategory->save();

            $subcategory->category()->sync([$request->integer('category_id')]);

            return $this->successResponse(
                new SubcategoryResource($subcategory),
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
     *     path="/categorization/categories/subcategories/update/{id}",
     *     tags={"Categorization › Subcategories"},
     *     summary="Editar una subcategoría",
     *     description="Reemplaza nombre y descripción; no cambia las categorías.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/SubcategoryRequest",
     *             example={"name": "JEAN MOM FIT", "description": "JEAN DE TIRO ALTO, PIERNA HOLGADA Y BOTA RECTA"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subcategoría editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubcategoryResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o subcategoría no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador de la subcategoría", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(SubcategoryUpdateRequest $request, $id)
    {
        try {
            $subcategory = Subcategory::findOrFail($id);
            $subcategory->name = $request->input('name');
            $subcategory->description = $request->input('description');
            $subcategory->save();

            $subcategory->load(['item', 'category']);

            return $this->successResponse(
                new SubcategoryResource($subcategory),
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
     *     path="/categorization/categories/subcategories/delete/{id}",
     *     tags={"Categorization › Subcategories"},
     *     summary="Desactivar una subcategoría",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subcategoría desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/SubcategoryResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(SubcategoryDeleteRequest $request, $id)
    {
        try {
            $subcategory = Subcategory::findOrFail($id);
            $subcategory->delete();

            $subcategory->load(['item', 'category']);

            return $this->successResponse(
                new SubcategoryResource($subcategory),
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
     *     path="/categorization/categories/subcategories/restore/{id}",
     *     tags={"Categorization › Subcategories"},
     *     summary="Restaurar una subcategoría desactivada",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subcategoría restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubcategoryResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(SubcategoryRestoreRequest $request, $id)
    {
        try {
            $subcategory = Subcategory::withTrashed()->findOrFail($id);
            $subcategory->restore();

            $subcategory->load(['item', 'category']);

            return $this->successResponse(
                new SubcategoryResource($subcategory),
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
