<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cache\Builder\BuilderAllRequest;
use App\Http\Requests\Cache\Builder\BuilderDeleteRequest;
use App\Http\Requests\Cache\Builder\BuilderStoreRequest;
use App\Http\Requests\Cache\Builder\BuilderUpdateRequest;
use App\Http\Resources\Cache\Builder\BuilderCollection;
use App\Http\Resources\Cache\Builder\BuilderResource;
use App\Services\BuilderService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Builder",
 *     type="object",
 *     description="Constructor tal como se guarda en caché.",
 *     @OA\Property(property="id", type="string", format="uuid", example="9b2f6c1e-4d7a-4f3b-8c2e-5a1d0e7f3b21"),
 *     @OA\Property(property="trademark_ids", type="array", @OA\Items(type="integer"), example={21}),
 *     @OA\Property(property="category_ids", type="array", @OA\Items(type="integer"), example={45}),
 *     @OA\Property(property="subcategory_ids", type="array", @OA\Items(type="integer"), example={112}),
 *     @OA\Property(
 *         property="percentages",
 *         type="object",
 *         description="Porcentaje por talla. La clave es el id de la talla.",
 *         additionalProperties=@OA\AdditionalProperties(type="number"),
 *         example={"101": 20, "102": 30, "103": 30, "104": 20}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BuilderDetail",
 *     type="object",
 *     description="Constructor con marcas, categorías, subcategorías y tallas ya resueltas (respuesta de `all`).",
 *     @OA\Property(property="id", type="string", format="uuid", example="9b2f6c1e-4d7a-4f3b-8c2e-5a1d0e7f3b21"),
 *     @OA\Property(
 *         property="trademarks",
 *         type="array",
 *         @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Subitem")}, example={"id": 21, "item_id": 15, "name": "DENIM CLASSIC", "description": "MARCA DE LÍNEA CLÁSICA", "settings": {}, "created_at": "2026-04-02T10:15:00.000000Z", "updated_at": "2026-04-02T10:15:00.000000Z", "deleted_at": null})
 *     ),
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *         @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Subitem")}, example={"id": 45, "item_id": 11, "name": "JEAN DAMA", "description": "PANTALÓN JEAN PARA DAMA", "settings": {}, "created_at": "2026-04-02T10:20:00.000000Z", "updated_at": "2026-04-02T10:20:00.000000Z", "deleted_at": null})
 *     ),
 *     @OA\Property(
 *         property="subcategories",
 *         type="array",
 *         @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Subitem")}, example={"id": 112, "item_id": 12, "name": "SKINNY", "description": "JEAN DAMA SKINNY TIRO ALTO", "settings": {}, "created_at": "2026-04-02T10:25:00.000000Z", "updated_at": "2026-04-02T10:25:00.000000Z", "deleted_at": null})
 *     ),
 *     @OA\Property(
 *         property="percentages",
 *         type="array",
 *         description="Solo incluye las tallas que todavía existen.",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="size", ref="#/components/schemas/Subitem"),
 *             @OA\Property(property="percentage", type="number", example=20)
 *         ),
 *         example={{"size": {"id": 101, "item_id": 16, "name": "6", "description": "TALLA 6", "settings": {}, "created_at": "2026-04-02T10:30:00.000000Z", "updated_at": "2026-04-02T10:30:00.000000Z", "deleted_at": null}, "percentage": 20}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BuilderResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="builder", ref="#/components/schemas/Builder")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BuilderListResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="builders", type="array", @OA\Items(ref="#/components/schemas/BuilderDetail"))),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="BuilderRequest",
 *     type="object",
 *     required={"trademark_id", "percentages"},
 *     @OA\Property(property="trademark_id", type="array", minItems=1, description="Marcas (al menos una).", @OA\Items(type="integer"), example={21}),
 *     @OA\Property(property="category_id", type="array", nullable=true, description="Categorías. Opcional.", @OA\Items(type="integer"), example={45}),
 *     @OA\Property(property="subcategory_id", type="array", nullable=true, description="Subcategorías. Exigen categoría y deben pertenecer a las categorías elegidas.", @OA\Items(type="integer"), example={112}),
 *     @OA\Property(
 *         property="percentages",
 *         type="object",
 *         description="Porcentaje (0 a 100) por id de talla. La suma debe ser exactamente 100 y cada talla debe pertenecer a alguna de las marcas elegidas.",
 *         additionalProperties=@OA\AdditionalProperties(type="number", minimum=0, maximum=100),
 *         example={"101": 20, "102": 30, "103": 30, "104": 20}
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="BuilderUuid",
 *     name="uuid",
 *     in="path",
 *     required=true,
 *     description="UUID del constructor.",
 *     @OA\Schema(type="string", format="uuid", example="9b2f6c1e-4d7a-4f3b-8c2e-5a1d0e7f3b21")
 * )
 */
class BuilderController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/cache/builders/all",
     *     tags={"Cache › Builders"},
     *     summary="Listar los constructores de curvas",
     *     description="Un constructor define el porcentaje de producción por talla para una combinación de marca, categoría y subcategoría. Se guardan en caché (no en la base de datos) y se identifican por UUID. Sin filtros ni paginación: devuelve todos.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de constructores cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BuilderListResponse")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(BuilderAllRequest $request)
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
     * @OA\Post(
     *     path="/cache/builders/store",
     *     tags={"Cache › Builders"},
     *     summary="Crear un constructor de curvas",
     *     description="No puede existir otro constructor para la misma combinación de marca, categoría y subcategoría.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BuilderRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Constructor creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BuilderResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o combinación ya registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"trademark_id": "Marcas", "category_id": "Categorías", "subcategory_id": "Subcategorías", "percentages": "Porcentajes"},
     *                 "errors": {"percentages": {"La suma de los porcentajes debe ser exactamente 100%."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(BuilderStoreRequest $request)
    {
        try {
            $data = $request->validated();

            $builder_id = (string) Str::uuid();

            $builder = [
                'id' => $builder_id,
                'trademark_ids' => $data['trademark_id'],
                'category_ids' => $data['category_id'] ?? [],
                'subcategory_ids' => $data['subcategory_id'] ?? [],
                'percentages' => $data['percentages'],
            ];

            Cache::forever("curve_builder:{$builder_id}", $builder);

            $builder_ids = Cache::get('curve_builder:ids', []);

            $builder_ids[] = $builder_id;

            Cache::forever('curve_builder:ids', array_values(array_unique($builder_ids)));

            foreach (app(BuilderService::class)->indexKeys($builder) as $index_key) {
                Cache::forever($index_key, $builder_id);
            }

            return $this->successResponse(
                new BuilderResource($builder),
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
     *     path="/cache/builders/update/{uuid}",
     *     tags={"Cache › Builders"},
     *     summary="Editar un constructor de curvas",
     *     description="Reemplaza marcas, categorías, subcategorías y porcentajes. Las combinaciones no pueden chocar con las de otro constructor.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/BuilderUuid"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/BuilderRequest",
     *             example={"trademark_id": {21}, "category_id": {45}, "subcategory_id": {112}, "percentages": {"101": 15, "102": 35, "103": 35, "104": 15}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Constructor editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BuilderResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos, constructor inexistente o combinación usada por otro constructor.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"trademark_id": "Marcas", "category_id": "Categorías", "subcategory_id": "Subcategorías", "percentages": "Porcentajes"},
     *                 "errors": {"builder": {"El constructor no existe."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(BuilderUpdateRequest $request, string $uuid)
    {
        try {
            $data = $request->validated();

            $builder_cache_key = "curve_builder:{$uuid}";

            $this->forget_index_keys(Cache::get($builder_cache_key), $uuid);

            $builder = [
                'id' => $uuid,
                'trademark_ids' => $data['trademark_id'],
                'category_ids' => $data['category_id'] ?? [],
                'subcategory_ids' => $data['subcategory_id'] ?? [],
                'percentages' => $data['percentages'],
            ];

            Cache::forever($builder_cache_key, $builder);

            foreach (app(BuilderService::class)->indexKeys($builder) as $index_key) {
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
     *     path="/cache/builders/delete/{uuid}",
     *     tags={"Cache › Builders"},
     *     summary="Eliminar un constructor de curvas",
     *     description="Eliminación definitiva: se borra de la caché y no se puede restaurar.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/BuilderUuid"),
     *     @OA\Response(
     *         response=200,
     *         description="Constructor eliminado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/BuilderResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El constructor no existe.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"uuid": "Constructor", "builder": "Constructor"},
     *                 "errors": {"builder": {"El constructor no existe."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(BuilderDeleteRequest $request, string $uuid)
    {
        try {
            $builder_cache_key = "curve_builder:{$uuid}";

            $builder = Cache::get($builder_cache_key);

            $this->forget_index_keys($builder, $uuid);

            Cache::forever(
                'curve_builder:ids',
                array_values(array_diff(Cache::get('curve_builder:ids', []), [$uuid]))
            );

            Cache::forget($builder_cache_key);

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

    private function forget_index_keys(?array $builder, string $uuid): void
    {
        if (! $builder) {
            return;
        }

        foreach (app(BuilderService::class)->indexKeys($builder) as $index_key) {
            if (Cache::get($index_key) === $uuid) {
                Cache::forget($index_key);
            }
        }
    }
}
