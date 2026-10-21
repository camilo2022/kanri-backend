<?php

namespace App\Http\Controllers;

use App\Exports\SupplyExport;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyAllRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyDeleteRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyExcelRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyFindRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyRestoreRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplySaveRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyStoreRequest;
use App\Http\Requests\Typification\SupplyType\Supply\SupplyUpdateRequest;
use App\Http\Resources\Typification\SupplyType\Supply\SupplyCollection;
use App\Http\Resources\Typification\SupplyType\Supply\SupplyResource;
use App\Imports\ImportErrorReport;
use App\Imports\SupplyImport;
use App\Models\Supply;
use App\Models\SupplyType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @OA\Tag(
 *     name="Supplies",
 *     description="Endpoints para gestionar insumos"
 * )
 *
 * @OA\PathItem(
 *     path="/Supplies",
 *     description="Rutas de gestión de insumos"
 * )
 *
 * @OA\Schema(
 *     schema="Supply",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="item_id", type="integer", example=10),
 *     @OA\Property(property="name", type="string", example="No Aplica"),
 *     @OA\Property(property="description", type="string", example="No Aplica"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=34),
 *         @OA\Property(property="name", type="string", example="Supplyes"),
 *         @OA\Property(property="description", type="string", example="Listado de insumos."),
 *         @OA\Property(property="settings", type="string", example="{}"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 * )
 */
class SupplyController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/supplies/all",
     *     tags={"Supplies"},
     *     summary="Listar Insumos",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filtro de Busqueda.",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="juan")
     *     ),
     *
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         description="Columna a ordenar.",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"id","name","description","created_at", "updated_at"}, example="name")
     *     ),
     *
     *     @OA\Parameter(
     *         name="dir",
     *         in="query",
     *         description="Dirección de ordenamiento.",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"asc","desc"}, example="asc")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Registros por página.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Parameter(
     *         name="with_trashed",
     *         in="query",
     *         description="Registros inactivos.",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de insumos cargada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplies",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Supply")
     *                 ),
     *
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
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function all(SupplyAllRequest $request, $supply_type_id)
    {
        try {
            $supplies = Supply::with(['supply_type', 'production_order_details'])
                ->whereHas('supply_type', fn ($r) => $r->where('id', $supply_type_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $supplies = $supplies->paginate($request->integer('per_page', $supplies->count()));

            logger()->debug('DATA', ['SUPPLY_TYPE' => $supply_type_id, 'RESULT' => $supplies]);

            return $this->successResponse(
                new SupplyCollection($supplies),
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
     *     path="/supplies/find/{id}",
     *     tags={"Supplies"},
     *     summary="Obtener un insumo específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del insumo",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Insumo cargado con éxito",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supply",
     *                     ref="#/components/schemas/Supply"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del insumo no registrado.",
     *
     *         @OA\JsonContent(
     *
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
     *
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function find(SupplyFindRequest $request, $id)
    {
        try {
            $supply = Supply::with(['supply_type'])->findOrFail($id);

            return $this->successResponse(
                new SupplyResource($supply),
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
     *     path="/supplies/store",
     *     tags={"Supplies"},
     *     summary="Crear un insumo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="ROLLO",
     *                 description="Nombre del insumo"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="ROLLO",
     *                 description="Descripción del insumo"
     *              )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Insumo creado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supply",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="NAME_SUPPLY"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_SUPPLY"),
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
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del insumo"),
     *                 @OA\Property(property="description", type="string", example="Descripción del insumo")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="Formato Inválido. El campo debe estar en mayúsculas.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function store(SupplyStoreRequest $request)
    {
        try {
            $supply = new Supply;
            $supply->name = $request->input('name');
            $supply->description = $request->input('description');
            $supply->settings = $request->input('settings');
            $supply->save();

            $supply->auditSync('supply_type', $request->integer('supply_type_id'));

            $supply->load(['supply_type']);

            collect($supply->supply_type->first()?->settings?->form ?? [])
                ->where('type', 'selectdinamic')
                ->each(function ($relationship) use ($supply, $request) {
                    if ($request->has("class.$relationship->field")) {
                        $supply->related($relationship->model)
                            ->sync(Arr::wrap($request->input("class.$relationship->field")));
                    }
                });

            return $this->successResponse(
                new SupplyResource($supply),
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
     *     path="/supplies/update/{id}",
     *     tags={"Supplies"},
     *     summary="Editar un insumo específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del insumo",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="ROLLO",
     *                 description="Nombre del insumo"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="INSUMOS ROLLOS",
     *                 description="Descripción del insumo"
     *              )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Insumo editado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supply",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_SUPPLY"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_SUPPLY"),
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
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del insumo"),
     *                 @OA\Property(property="description", type="string", example="Descripción del insumo")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="Formato Inválido. El campo debe estar en mayúsculas.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function update(SupplyUpdateRequest $request, $id)
    {
        try {
            $supply = Supply::findOrFail($id);
            $supply->name = $request->input('name');
            $supply->description = $request->input('description');
            $supply->settings = $request->input('settings');
            $supply->save();

            $supply->load(['supply_type']);

            collect($supply->supply_type->first()?->settings?->form ?? [])
                ->where('type', 'selectdinamic')
                ->each(function ($relationship) use ($supply, $request) {

                    if ($request->has("class.$relationship->field")) {
                        $supply->related($relationship->model)
                            ->sync(Arr::wrap($request->input("class.$relationship->field")));
                    }
                });

            return $this->successResponse(
                new SupplyResource($supply),
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
     *     path="/supplies/delete/{id}",
     *     tags={"Supplies"},
     *     summary="Desactivar un insumo específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del insumo",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Insumo desactivado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supply",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_SUPPLY"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_SUPPLY"),
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
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del insumo")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function delete(SupplyDeleteRequest $request, $id)
    {
        try {
            $supply = Supply::findOrFail($id);
            $supply->delete();

            return $this->successResponse(
                new SupplyResource($supply),
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
     *     path="/supplies/restore/{id}",
     *     tags={"Supplies"},
     *     summary="Activar un insumo específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del insumo",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Insumo activado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supply",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_SUPPLY"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_SUPPLY"),
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
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del insumo")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function restore(SupplyRestoreRequest $request, $id)
    {
        try {
            $supply = Supply::withTrashed()->findOrFail($id);
            $supply->restore();

            return $this->successResponse(
                new SupplyResource($supply),
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

    public function export(SupplyExcelRequest $request, $supply_type_id)
    {
        return Excel::download(new SupplyExport($supply_type_id), 'supplys.xlsx');
    }

    public function import(Request $request, $supply_type_id)
    {
        try {
            $supply_type = SupplyType::findOrFail($supply_type_id);

            $import = new SupplyImport($supply_type);

            Excel::import($import, $request->file('file'));

            $formRequest = new SupplySaveRequest;

            $supplys = $import->builder()->build();

            $duplicates = $import->builder()->duplicateSupplys();

            $validator = Validator::make(
                [
                    'supply_type_id' => $supply_type_id,
                    'supplys' => $supplys,
                ],
                $formRequest->rules(),
                $formRequest->messages(),
                $formRequest->attributes()
            );

            $formRequest->duplicates = $duplicates;

            $formRequest->setContainer(app());

            $formRequest->withValidator($validator);

            if ($validator->fails()) {
                $path = app(ImportErrorReport::class)->generate(
                    $validator->errors()->toArray(),
                    $supplys,
                    $supply_type
                );

                return response()->json([
                    'message' => 'La importación contiene errores.',
                    'errors' => $validator->errors(),
                    'file' => $path,
                ], 422);
            }

            DB::transaction(function () use ($supplys, $supply_type_id) {
                foreach ($supplys as $item) {
                    $supply = Supply::updateOrCreate(
                        ['name' => $item['name']],
                        [
                            'description' => $item['description'],
                            'settings' => $item['settings'] ?? null,
                        ]
                    );

                    $supply->auditSync('supply_type', $supply_type_id);

                    $supply->load(['supply_type']);

                    collect($supply->supply_type->first()?->settings?->form ?? [])
                        ->where('type', 'selectdinamic')
                        ->each(function ($relationship) use ($supply, $item) {
                            if (Arr::has($item, "class.$relationship->field")) {
                                $supply->related($relationship->model)
                                    ->sync(
                                        Arr::wrap(
                                            Arr::get($item, "class.$relationship->field")
                                        )
                                    );
                            }
                        });
                }
            });

            return $this->successResponse(
                $supplys,
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
