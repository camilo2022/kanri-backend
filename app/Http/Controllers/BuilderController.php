<?php

namespace App\Http\Controllers;

use App\Http\Requests\Builder\BuilderDeleteRequest;
use App\Http\Requests\Builder\BuilderStoreRequest;
use App\Http\Requests\Builder\BuilderUpdateRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaAllRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaDeleteRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaFindRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaRestoreRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaStoreRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaUpdateRequest;
use App\Http\Resources\Builder\BuilderCollection;
use App\Http\Resources\Builder\BuilderResource;
use App\Http\Resources\OrganizationalStructure\Area\AreaCollection;
use App\Http\Resources\OrganizationalStructure\Area\AreaResource;
use App\Models\Area;
use App\Models\Category;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use App\Models\Subcategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Area",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="item_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="users"),
 *     @OA\Property(property="description", type="string", example="area que gestiona los usuarios"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Áreas"),
 *         @OA\Property(property="description", type="string", example="Listado de áreas disponibles en la organización."),
 *         @OA\Property(property="settings", type="string", example="{}"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(
 *         property="positions",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="item_id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="No aplica"),
 *             @OA\Property(property="description", type="string", example="No aplica"),
 *             @OA\Property(property="settings", type="string", example="{}"),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *             @OA\Property(
 *                 property="pivot",
 *                 type="object",
 *                 @OA\Property(property="model_type", type="string", example="App\\Models\\Position"),
 *                 @OA\Property(property="subitem_id", type="integer", example=1),
 *                 @OA\Property(property="model_id", type="integer", example=1),
 *             )
 *         )
 *     )
 * )
 */
class BuilderController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/organizational_structure/areas/all",
     *     tags={"Organizational Structure"},
     *     summary="Listar áreas",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filtro de Busqueda.",
     *         required=false,
     *         @OA\Schema(type="string", example="LIDER DE MARCAS PROPIAS")
     *     ),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         description="Columna a ordenar.",
     *         required=false,
     *         @OA\Schema(type="string", enum={"id","name","description","created_at", "updated_at"}, example="name")
     *     ),
     *     @OA\Parameter(
     *         name="dir",
     *         in="query",
     *         description="Dirección de ordenamiento.",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc","desc"}, example="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Registros por página.",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="with_trashed",
     *         in="query",
     *         description="Registros inactivos.",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de áreas cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="areas",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Area")
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(
     *                         property="pagination",
     *                         type="object",
     *                         @OA\Property(property="total", type="integer", example=1),
     *                         @OA\Property(property="count", type="integer", example=1),
     *                         @OA\Property(property="per_page", type="integer", example=1),
     *                         @OA\Property(property="current_page", type="integer", example=1),
     *                         @OA\Property(property="total_pages", type="integer", example=1)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function all()
    {
        try {
            $builder_ids = Cache::get('curve_builder:ids', []);

            $builders = collect($builder_ids)
                ->map(function ($builder_id) {
                    return Cache::get("curve_builder:{$builder_id}");
                })
                ->filter()
                ->values();

            return $this->successResponse(
                new BuilderCollection($builders),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {

            return $this->errorResponse(
                [
                    'message' => $this->getMessage(
                        class_basename($e)
                    ),
                    'error' => $e->getMessage()
                ],
                $this->getCode(
                    class_basename($e)
                )
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/organizational_structure/areas/find/{id}",
     *     tags={"Organizational Structure"},
     *     summary="Obtener un área especifica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del área",
     *         required=true,
     *         @OA\Schema(type="integer", example=234)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Área cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="area",
     *                     ref="#/components/schemas/Area"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del área no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example=1)
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function find(AreaFindRequest $request, $id)
    {
        try {
            $area = Area::with(['item', 'positions'])
                ->findOrFail($id);

            return $this->successResponse(
                new AreaResource($area),
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
     *     path="/organizational_structure/areas/store",
     *     tags={"Organizational Structure"},
     *     summary="Crear un área",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="ADMINISTRACION",
     *                 description="Nombre del Área"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="ADMINISTRACION",
     *                 description="Descripción del Área"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Área creada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="area",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="name_module_example"),
     *                     @OA\Property(property="description", type="string", example="description_module_example"),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="id", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del Área"),
     *                 @OA\Property(property="description", type="string", example="Descripción del Área")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="El campo debe estar en mayúsculas.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function store(BuilderStoreRequest $request)
    {
        try {
            $data = $request->validated();

            $trademark_ids = $data['trademark_id'];
            $category_ids = $data['category_id'] ?? [];
            $subcategory_ids = $data['subcategory_id'] ?? [];
            $percentages = $data['percentages'];

            $builder_id = (string) Str::uuid();

            $builder = [
                'id' => $builder_id,
                'trademark_ids' => $trademark_ids,
                'category_ids' => $category_ids,
                'subcategory_ids' => $subcategory_ids,
                'percentages' => $percentages,
            ];

            $builder_cache_key = "curve_builder:{$builder_id}";

            Cache::forever($builder_cache_key, $builder);

            $builder_ids = Cache::get('curve_builder:ids', []);

            $builder_ids[] = $builder_id;

            Cache::forever('curve_builder:ids', array_unique($builder_ids));

            $categories = Category::with(['subcategories' => fn($query) => $query->whereIn('id', $subcategory_ids) ])
                ->whereIn('id', $category_ids)
                ->get();

            $index_keys = [];

            foreach ($trademark_ids as $trademark_id) {
                if($categories->isEmpty()) {
                    $index_keys[] = "curve_builder:index:{$trademark_id}";
                    continue;
                }

                foreach ($categories as $category) {
                    if($category->subcategories->isEmpty()) {
                        $index_keys[] = "curve_builder:index:" . "{$trademark_id}:" . "{$category->id}";
                        continue;
                    }
                    foreach ($category->subcategories as $subcategory) {
                        $index_keys[] = "curve_builder:index:{$trademark_id}.{$category->id}.{$subcategory->id}";
                    }
                }
            }

            foreach (array_unique($index_keys) as $index_key) {
                Cache::forever($index_key, $builder_id);
            }

            return $this->successResponse(
                new BuilderResource($builder),
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
     * @OA\Put(
     *     path="/organizational_structure/areas/update/{id}",
     *     tags={"Organizational Structure"},
     *     summary="Editar un área específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del área",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Usuarios",
     *                 description="Nombre del Área"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="Lista de Usuarios",
     *                 description="Descripción del Área"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Área editada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="area",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_AREA"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_AREA"),
     *                     @OA\Property(property="settings", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del Área"),
     *                 @OA\Property(property="description", type="string", example="Descripción del Área")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="El campo debe estar en mayúsculas.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function update(BuilderUpdateRequest $request, string $uuid)
    {
        try {

            $data = $request->validated();

            $trademark_ids = $data['trademark_id'];
            $category_ids = $data['category_id'] ?? [];
            $subcategory_ids = $data['subcategory_id'] ?? [];
            $percentages = $data['percentages'];

            $builder_cache_key = "curve_builder:{$uuid}";
            $builder = Cache::get($builder_cache_key);

            $subcategories = Subcategory::with('category')
                ->whereIn('id', $subcategory_ids)
                ->get();

            $old_trademark_ids = $builder['trademark_ids'] ?? [];
            $old_category_ids = $builder['category_ids'] ?? [];
            $old_subcategory_ids = $builder['subcategory_ids'] ?? [];

            if (empty($old_category_ids)) {
                foreach ($old_trademark_ids as $trademark_id) {

                    $index_key = "curve_builder:index:{$trademark_id}";
                    Cache::forget($index_key);
                }
            } elseif (empty($old_subcategory_ids)) {

                foreach ($old_trademark_ids as $trademark_id) {
                    foreach ($old_category_ids as $category_id) {
                        $index_key =
                            "curve_builder:index:"
                            . "{$trademark_id}:"
                            . "{$category_id}";

                        Cache::forget($index_key);
                    }
                }
            } else {

                $old_subcategories = Subcategory::with('category')
                    ->whereIn('id', $old_subcategory_ids)
                    ->get();

                foreach ($old_trademark_ids as $trademark_id) {

                    foreach ($old_subcategories as $subcategory) {

                        $category_id = $subcategory->category
                            ->first()
                            ->id;

                        $index_key =
                            "curve_builder:index:"
                            . "{$trademark_id}:"
                            . "{$category_id}:"
                            . "{$subcategory->id}";

                        Cache::forget($index_key);
                    }
                }
            }

            $builder = [
                'id' => $uuid,
                'trademark_ids' => $trademark_ids,
                'category_ids' => $category_ids,
                'subcategory_ids' => $subcategory_ids,
                'percentages' => $percentages,
            ];

            Cache::forever($builder_cache_key, $builder);

            $index_keys = [];

            if (empty($category_ids)) {

                foreach ($trademark_ids as $trademark_id) {

                    $index_keys[] =
                        "curve_builder:index:{$trademark_id}";
                }
            } else {

                $categories_with_subcategories = $subcategories
                    ->pluck('category.id')
                    ->unique()
                    ->map(fn($id) => (string) $id)
                    ->toArray();


                foreach ($trademark_ids as $trademark_id) {
                    foreach ($subcategories as $subcategory) {

                        $category_id = $subcategory->category
                            ->first()
                            ->id;

                        $index_keys[] =
                            "curve_builder:index:"
                            . "{$trademark_id}:"
                            . "{$category_id}:"
                            . "{$subcategory->id}";
                    }

                    foreach ($category_ids as $category_id) {

                        if (!in_array((string) $category_id, $categories_with_subcategories, true)) {
                            $index_keys[] =
                                "curve_builder:index:"
                                . "{$trademark_id}:"
                                . "{$category_id}";
                        }
                    }
                }
            }

            foreach (array_unique($index_keys) as $index_key) {
                Cache::forever($index_key, $uuid);
            }

            return $this->successResponse(
                new BuilderResource($builder),
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
     *     path="/organizational_structure/areas/delete/{id}",
     *     tags={"Organizational Structure"},
     *     summary="Desactivar un área específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del área",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Área desactivada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="area",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="name_module_example"),
     *                     @OA\Property(property="description", type="string", example="description_module_example"),
     *                     @OA\Property(property="settings", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del área")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function delete(BuilderDeleteRequest $request, string $uuid)
    {
        try {

            $builder_cache_key = "curve_builder:{$uuid}";

            $builder = Cache::get($builder_cache_key);

            $trademark_ids = $builder['trademark_ids'] ?? [];
            $category_ids = $builder['category_ids'] ?? [];
            $subcategory_ids = $builder['subcategory_ids'] ?? [];

            if (empty($category_ids)) {

                foreach ($trademark_ids as $trademark_id) {
                    $index_key = "curve_builder:index:{$trademark_id}";
                    Cache::forget($index_key);
                }
            } elseif (empty($subcategory_ids)) {
                foreach ($trademark_ids as $trademark_id) {
                    foreach ($category_ids as $category_id) {
                        $index_key = "curve_builder:index:" . "{$trademark_id}:" . "{$category_id}";
                        Cache::forget($index_key);
                    }
                }
            } else {

                $subcategories = Subcategory::with('category')
                    ->whereIn('id', $subcategory_ids)
                    ->get();

                foreach ($trademark_ids as $trademark_id) {
                    foreach ($subcategories as $subcategory) {

                        $category_id = $subcategory->category
                            ->first()
                            ->id;

                        $index_key = "curve_builder:index:" . "{$trademark_id}:" . "{$category_id}:" . "{$subcategory->id}";
                        Cache::forget($index_key);
                    }
                }
            }

            Cache::forget($builder_cache_key);

            return response()->json([
                'message' => 'Constructor eliminado correctamente.'
            ], 200);
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
     *     path="/organizational_structure/areas/restore/{id}",
     *     tags={"Organizational Structure"},
     *     summary="Activar un área específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del área",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Área activada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="area",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="name_module_example"),
     *                     @OA\Property(property="description", type="string", example="description_module_example"),
     *                     @OA\Property(property="settings", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del área")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function restore(AreaRestoreRequest $request, $id)
    {
        try {
            $area = Area::withTrashed()->findOrFail($id);
            $area->restore();

            return $this->successResponse(
                new AreaResource($area),
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
