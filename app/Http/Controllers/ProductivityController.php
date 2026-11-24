<?php

namespace App\Http\Controllers;

use App\Http\Requests\Productivity\ProductivityAllRequest;
use App\Http\Requests\Productivity\ProductivityDeleteRequest;
use App\Http\Requests\Productivity\ProductivityFindRequest;
use App\Http\Requests\Productivity\ProductivityRestoreRequest;
use App\Http\Requests\Productivity\ProductivityStoreRequest;
use App\Http\Requests\Productivity\ProductivityUpdateRequest;
use App\Http\Resources\Productivity\ProductivityCollection;
use App\Http\Resources\Productivity\ProductivityResource;
use App\Models\Productivity;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Productivities",
 *     description="Endpoints para gestionar productividades"
 * )
 *
 * @OA\PathItem(
 *     path="/productivities",
 *     description="Rutas de gestión de productividades"
 * )
 *
 * @OA\Schema(
 *     schema="Productivity",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="model_type", type="string", example="App\\Models\\Process"),
 *     @OA\Property(property="model_id", type="integer", example=1),
 *     @OA\Property(property="date", type="string", format="date", example="2026-10-21"),
 *     @OA\Property(property="start_time", type="string", example="07:00:00"),
 *     @OA\Property(property="end_time", type="string", example="17:00:00"),
 *     @OA\Property(property="settings", type="object", example={}),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-10-21T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-10-21T13:17:29.000000Z")
 * )
 */
class ProductivityController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/productivities/all",
     *     tags={"Productivities"},
     *     summary="Listar las productividades de un proceso",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="process_id",
     *         in="query",
     *         description="Identificador del proceso.",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         description="Columna a ordenar.",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"id","date","start_time","end_time","created_at","updated_at"}, example="date")
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de productividades cargada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="productivities",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Productivity")
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
     *         response=422,
     *         description="Identificador del proceso no registrado.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="process_id", type="string", example="Identificador del proceso.")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="process_id",
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
    public function all(ProductivityAllRequest $request)
    {
        try {
            $productivities = Productivity::with(['model', 'productivity_details' => ['movement_details' => ['movement_detail_quantities']]])
                ->when($request->filled('model_type') && $request->filled('model_id'),
                    function ($query) use ($request) {
                        return $query->where('model_type', $request->input('model_type'))
                            ->where('model_id', $request->integer('model_id'));
                    }
                )
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('date'), function ($query) use ($request) {
                    return $query->whereDate('date', $request->input('date'));
                })
                ->when($request->filled('date_from'), function ($query) use ($request) {
                    return $query->whereDate('date', '>=', $request->input('date_from'));
                })
                ->when($request->filled('date_to'), function ($query) use ($request) {
                    return $query->whereDate('date', '<=', $request->input('date_to'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $productivities = $productivities->paginate(
                $request->integer('per_page', max($productivities->count(), 1))
            );

            return $this->successResponse(
                new ProductivityCollection($productivities),
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

    public function store(ProductivityStoreRequest $request)
    {
        try {
            $productivity = new Productivity;
            $productivity->model_type = $request->input('model_type');
            $productivity->model_id = $request->input('model_id');
            $productivity->date = $request->input('date');
            $productivity->start_time = $request->input('start_time');
            $productivity->end_time = $request->input('end_time');
            $productivity->settings = $request->input('settings');

            $productivity->save();

            return $this->successResponse(
                new ProductivityResource($productivity),
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
     *     path="/productivity/find/{id}",
     *     tags={"Productivities"},
     *     summary="Consultar un turno activo",
     *     description="Devuelve el turno con su estado (Por iniciar, En curso o Terminado) y la meta por hora. Lo usa el modal de editar turno.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *
     *     @OA\Response(response=200, description="Turno", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity", ref="#/components/schemas/Productivity")),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(ProductivityFindRequest $request, $id)
    {
        try {
            return $this->successResponse(
                new ProductivityResource(Productivity::with([
                    'model' => ['before_processes', 'employees' => ['person'], 'subprocesses' => ['employees' => ['person'], 'after_subprocesses', 'operations' => ['employees' => ['person'], 'after_operations', 'after_subprocesses']]],
                    'productivity_details' => [
                        'model',
                        'production_order' => ['technical_sheet' => ['product', 'garment_type'], 'production_order_details_product' => ['production_order_detail_quantities']],
                        'rule',
                        'employees' => ['person'],
                        'stops',
                        'movement_details' => ['movement_detail_quantities'],
                    ],
                ])->findOrFail($id)),
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
     * @OA\Put(
     *     path="/productivity/update/{id}",
     *     tags={"Productivities"},
     *     summary="Editar un turno",
     *     description="Por iniciar: se puede cambiar fecha, horario y meta por hora. En curso: solo la hora de fin (posterior a la hora actual; lo demás se ignora). Terminado o desactivado: 422. En todos los casos no puede cruzarse con otro turno del proceso. Si cambia el inicio (hora o día), la programación sin iniciar del turno se corre lo mismo (start y settings.scheduled.start/end). Las paradas ya no son del turno: se registran en cada registro de trabajo.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *
     *         @OA\Property(property="date", type="string", format="date", example="2026-10-26"),
     *         @OA\Property(property="start_time", type="string", example="07:00"),
     *         @OA\Property(property="end_time", type="string", example="17:00"),
     *         @OA\Property(property="settings", type="object",
     *             @OA\Property(property="goal_hour", type="number", example=300)
     *         )
     *     )),
     *
     *     @OA\Response(response=200, description="Turno actualizado", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity", ref="#/components/schemas/Productivity")),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(ProductivityUpdateRequest $request, $id)
    {
        try {
            $productivity = Productivity::findOrFail($id);
            $starts_at = $productivity->startsAt();
            $productivity->date = $request->input('date');
            $productivity->start_time = $request->input('start_time');
            $productivity->end_time = $request->input('end_time');
            $productivity->settings = $request->input('settings');

            $productivity->save();

            // Si el turno se corre (otra hora de inicio u otro día), su programación sin iniciar se corre lo mismo
            $seconds = (int) round($starts_at->diffInSeconds($productivity->startsAt(), false));

            if ($seconds !== 0) {
                foreach ($productivity->productivity_details()->scheduled()->get() as $productivity_detail) {
                    $settings = json_decode(json_encode($productivity_detail->settings), true) ?? [];

                    foreach (['start', 'end'] as $key) {
                        if (isset($settings['scheduled'][$key])) {
                            $settings['scheduled'][$key] = Carbon::parse($settings['scheduled'][$key])->addSeconds($seconds)->format('Y-m-d H:i:s');
                        }
                    }

                    $productivity_detail->start = $productivity_detail->start->copy()->addSeconds($seconds);
                    $productivity_detail->settings = $settings;
                    $productivity_detail->save();
                }
            }

            return $this->successResponse(
                new ProductivityResource($productivity->load('model')),
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
     *     path="/productivity/delete/{id}",
     *     tags={"Productivities"},
     *     summary="Desactivar un turno",
     *     description="Solo un turno que todavía no ha iniciado y sin trabajo registrado.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *
     *     @OA\Response(response=200, description="Turno desactivado", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity", ref="#/components/schemas/Productivity")),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(ProductivityDeleteRequest $request, $id)
    {
        try {
            $productivity = Productivity::findOrFail($id);
            $productivity->delete();

            return $this->successResponse(
                new ProductivityResource($productivity),
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
     *     path="/productivity/restore/{id}",
     *     tags={"Productivities"},
     *     summary="Activar un turno desactivado",
     *     description="No debe cruzarse con otro turno activo del mismo proceso.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *
     *     @OA\Response(response=200, description="Turno activado", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity", ref="#/components/schemas/Productivity")),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(ProductivityRestoreRequest $request, $id)
    {
        try {
            $productivity = Productivity::onlyTrashed()->findOrFail($id);
            $productivity->restore();

            return $this->successResponse(
                new ProductivityResource($productivity),
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
