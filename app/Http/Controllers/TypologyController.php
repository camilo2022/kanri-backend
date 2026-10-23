<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\Typology\TypologyAllRequest;
use App\Http\Requests\Typification\Typology\TypologyDeleteRequest;
use App\Http\Requests\Typification\Typology\TypologyFindRequest;
use App\Http\Requests\Typification\Typology\TypologyRestoreRequest;
use App\Http\Requests\Typification\Typology\TypologyStoreRequest;
use App\Http\Requests\Typification\Typology\TypologyUpdateRequest;
use App\Http\Resources\Typification\Typology\TypologyCollection;
use App\Http\Resources\Typification\Typology\TypologyResource;
use App\Models\Typology;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="TypologySettings",
 *     type="object",
 *     required={"code"},
 *     description="Configuración de la tipología. Se guarda tal cual llega (reemplaza la anterior).",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos.", example="1003")
 * )
 *
 * @OA\Schema(
 *     schema="Typology",
 *     type="object",
 *     description="Tipología (PREMIUM MODA, RIGIDA ECONOMICO…), asociada a los procesos que aplican. Se usa en el detalle de la ficha técnica.",
 *     @OA\Property(property="id", type="integer", example=489),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=41),
 *     @OA\Property(property="name", type="string", example="BÁSICO STRETCH"),
 *     @OA\Property(property="description", type="string", example="JEAN BÁSICO EN TELA STRETCH"),
 *     @OA\Property(property="settings", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/TypologySettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 11:45:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 11:45:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=41),
 *         @OA\Property(property="name", type="string", example="Tipologias"),
 *         @OA\Property(property="description", type="string", example="Listado de tipologias"),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="TypologyResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="typology", allOf={
 *             @OA\Schema(ref="#/components/schemas/Typology"),
 *             @OA\Schema(@OA\Property(property="processes", type="array", @OA\Items(ref="#/components/schemas/Subitem")))
 *         }),
 *         @OA\Property(property="model_types", type="object", description="Modelos disponibles para campos `selectdinamic` (igual que en tipos de insumo).", additionalProperties=true)
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="TypologyListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="typologies", type="array", @OA\Items(allOf={
 *             @OA\Schema(ref="#/components/schemas/Typology"),
 *             @OA\Schema(
 *                 @OA\Property(property="processes", type="array", @OA\Items(ref="#/components/schemas/Subitem")),
 *                 @OA\Property(property="technical_sheet_details", type="array", @OA\Items(type="object", additionalProperties=true))
 *             )
 *         })),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="TypologyRequest",
 *     type="object",
 *     required={"name", "description", "settings", "processes"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre las tipologías.", example="BÁSICO STRETCH"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="JEAN BÁSICO EN TELA STRETCH"),
 *     @OA\Property(property="settings", ref="#/components/schemas/TypologySettings"),
 *     @OA\Property(property="processes", type="array", description="Procesos de la tipología. Al crear se agregan; al editar reemplazan los anteriores.", @OA\Items(type="integer"), example={205, 206, 208})
 * )
 */
class TypologyController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/typologies/all",
     *     tags={"Typification › Typologies"},
     *     summary="Listar las tipologías",
     *     description="Cada tipología trae sus procesos y los detalles de ficha técnica que la usan.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="process_id",
     *         in="query",
     *         required=false,
     *         description="Solo las tipologías asociadas a este proceso. No se valida.",
     *         @OA\Schema(type="integer", example=205)
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
     *         description="Lista de tipologías cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TypologyListResponse")
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
    public function all(TypologyAllRequest $request)
    {
        try {
            $typologies = Typology::with(['processes', 'technical_sheet_details'])
                ->when($request->filled('process_id'), function ($query) use ($request) {
                    return $query->whereHas('processes', function ($q) use ($request) {
                        $q->where('id', $request->input('process_id'));
                    });
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $typologies = $typologies->paginate($request->integer('per_page', $typologies->count()));

            return $this->successResponse(
                new TypologyCollection($typologies),
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
     *     path="/typification/typologies/find/{id}",
     *     tags={"Typification › Typologies"},
     *     summary="Obtener una tipología",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipología cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TypologyResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(TypologyFindRequest $request, $id)
    {
        try {
            $typology = Typology::findOrFail($id);

            return $this->successResponse(
                new TypologyResource($typology),
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
     *     path="/typification/typologies/store",
     *     tags={"Typification › Typologies"},
     *     summary="Crear una tipología",
     *     description="Crea la tipología y la asocia a los procesos indicados.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/TypologyRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipología creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TypologyResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código", "processes": "Procesos asociados"},
     *                 "errors": {"processes": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(TypologyStoreRequest $request)
    {
        try {
            $typology = new Typology;
            $typology->name = $request->input('name');
            $typology->description = $request->input('description');
            $typology->settings = $request->input('settings');

            $typology->save();

            $typology->auditAttach('processes', $request->array('processes'));

            return $this->successResponse(
                new TypologyResource($typology),
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
     *     path="/typification/typologies/update/{id}",
     *     tags={"Typification › Typologies"},
     *     summary="Editar una tipología",
     *     description="Reemplaza nombre, descripción, `settings` y la lista de procesos.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/TypologyRequest",
     *             example={"name": "BÁSICO STRETCH", "description": "JEAN BÁSICO EN TELA STRETCH CON LAVADO", "settings": {"code": "1003"}, "processes": {205, 206, 208, 211}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipología editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TypologyResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipología no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de tela", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código", "processes": "Procesos"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(TypologyUpdateRequest $request, $id)
    {
        try {
            $typology = Typology::findOrFail($id);
            $typology->name = $request->input('name');
            $typology->description = $request->input('description');
            $typology->settings = $request->input('settings');

            $typology->save();

            $typology->auditSync('processes', $request->array('processes'));

            return $this->successResponse(
                new TypologyResource($typology),
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
     * @OA\Delete(
     *     path="/typification/typologies/delete/{id}",
     *     tags={"Typification › Typologies"},
     *     summary="Desactivar una tipología",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipología desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/TypologyResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(TypologyDeleteRequest $request, $id)
    {
        try {
            $typology = Typology::findOrFail($id);
            $typology->delete();

            return $this->successResponse(
                new TypologyResource($typology),
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
     * @OA\Patch(
     *     path="/typification/typologies/restore/{id}",
     *     tags={"Typification › Typologies"},
     *     summary="Restaurar una tipología desactivada",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipología restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/TypologyResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(TypologyRestoreRequest $request, $id)
    {
        try {
            $typology = Typology::withTrashed()->findOrFail($id);
            $typology->restore();

            return $this->successResponse(
                new TypologyResource($typology),
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
}
