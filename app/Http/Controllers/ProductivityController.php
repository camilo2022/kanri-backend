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
 * @OA\Schema(
 *     schema="Productivity",
 *     type="object",
 *     description="Turno de productividad de un proceso, subproceso u operación en un día. `status`, `duration_hours` y `goal_day` se calculan en el servidor. Las claves `productivity_details` y `planned_orders` solo vienen cuando el controlador carga el detalle (`all` y `find`).",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="model_type", type="string", description="Elemento al que pertenece el turno. Al crear o editar solo se admite un proceso.", enum={"App\Models\Process", "App\Models\Subprocess", "App\Models\Operation"}, example="App\Models\Process"),
 *     @OA\Property(property="model_id", type="integer", example=206),
 *     @OA\Property(property="date", type="string", format="date", example="2026-10-21"),
 *     @OA\Property(property="start_time", type="string", example="07:00:00"),
 *     @OA\Property(property="end_time", type="string", example="17:00:00"),
 *     @OA\Property(property="settings", type="object", description="Solo se expone la meta por hora.", @OA\Property(property="goal_hour", type="number", nullable=true, example=300)),
 *     @OA\Property(property="duration_hours", type="integer", description="Horas entre el inicio y el fin.", example=10),
 *     @OA\Property(property="goal_day", type="number", nullable=true, description="Meta del día: `goal_hour` por `duration_hours`. Nulo si no hay meta por hora.", example=3000),
 *     @OA\Property(property="status", type="string", enum={"Por iniciar", "En curso", "Terminado"}, description="Según la hora actual frente al horario del turno.", example="Por iniciar"),
 *     @OA\Property(property="created_at", type="string", example="2026-10-21 13:17:29"),
 *     @OA\Property(property="updated_at", type="string", example="2026-10-21 13:17:29"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="model", type="object", nullable=true, additionalProperties=true, description="Proceso, subproceso u operación del turno, tal cual lo serializa Eloquent."),
 *     @OA\Property(property="productivity_details", type="array", description="Detalle de trabajo del turno (programación y registros). En `all` trae sus movimientos con cantidades.", @OA\Items(type="object", additionalProperties=true))
 * )
 *
 * @OA\Schema(
 *     schema="ProductivityResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="productivity", ref="#/components/schemas/Productivity")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProductivityListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="productivities", type="array", @OA\Items(ref="#/components/schemas/Productivity")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProductivityStoreRequest",
 *     type="object",
 *     required={"model_type", "model_id", "date", "start_time", "end_time", "settings"},
 *     @OA\Property(property="model_type", type="string", enum={"App\Models\Process"}, description="Solo se crean turnos de procesos.", example="App\Models\Process"),
 *     @OA\Property(property="model_id", type="integer", description="Proceso del turno.", example=206),
 *     @OA\Property(property="date", type="string", format="date", description="No puede ser anterior a hoy.", example="2026-10-26"),
 *     @OA\Property(property="start_time", type="string", description="Formato H:i.", example="07:00"),
 *     @OA\Property(property="end_time", type="string", description="Formato H:i, posterior a `start_time`.", example="17:00"),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         @OA\Property(property="goal_hour", type="number", minimum=1, description="Meta por hora. Obligatoria si el proceso se mide por lotes (`productivity_measurement = lots`).", example=300)
 *     )
 * )
 */
class ProductivityController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/productivity/all",
     *     tags={"Productivities"},
     *     summary="Listar los turnos de un proceso, subproceso u operación",
     *     description="Lista los turnos del elemento indicado con `model_type` y `model_id` (ambos obligatorios), con su estado, la meta y el detalle de trabajo de cada turno. Se puede acotar a un día (`date`) o a un rango (`date_from`, `date_to`). Requiere el permiso `productivity.all`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="model_type", in="query", required=true, description="Tipo de elemento.", @OA\Schema(type="string", enum={"App\Models\Process", "App\Models\Subprocess", "App\Models\Operation"}, example="App\Models\Process")),
     *     @OA\Parameter(name="model_id", in="query", required=true, description="Identificador del proceso, subproceso u operación, según `model_type`.", @OA\Schema(type="integer", example=206)),
     *     @OA\Parameter(name="date", in="query", required=false, description="Solo los turnos de ese día (Y-m-d).", @OA\Schema(type="string", format="date", example="2026-10-26")),
     *     @OA\Parameter(name="date_from", in="query", required=false, description="Turnos desde esta fecha (Y-m-d).", @OA\Schema(type="string", format="date", example="2026-10-01")),
     *     @OA\Parameter(name="date_to", in="query", required=false, description="Turnos hasta esta fecha (Y-m-d). No puede ser anterior a `date_from`.", @OA\Schema(type="string", format="date", example="2026-10-31")),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "date", "start_time", "end_time", "created_at", "updated_at"}, example="date")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(name="with_trashed", in="query", required=false, description="Incluir los turnos desactivados. El Request no lo valida; el controlador lo lee como booleano.", @OA\Schema(type="boolean", example=false)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de turnos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductivityListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"model_type": "Tipo de elemento (proceso, subproceso u operación).", "model_id": "Identificador del elemento."},
     *                 "errors": {"model_id": {"No hay ningún registro."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
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

    /**
     * @OA\Post(
     *     path="/productivity/store",
     *     tags={"Productivities"},
     *     summary="Crear un turno",
     *     description="Crea el turno de un proceso en un día. No se permiten fechas anteriores a hoy ni horarios que se crucen con otro turno del mismo proceso ese día. Si el proceso se mide por lotes, `settings.goal_hour` es obligatorio. Hoy responde 200 (no 201), tal como está escrito el controlador. Requiere el permiso `productivity.store`.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProductivityStoreRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Turno creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductivityResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"date": "Fecha del turno.", "start_time": "Hora de inicio."},
     *                 "errors": {"start_time": {"Ya existe un turno para este elemento que se cruza con este horario."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
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
