<?php

namespace App\Http\Controllers;

use App\Http\Requests\Productivity\ProductivityAllRequest;
use App\Http\Requests\Productivity\ProductivityStoreRequest;
use App\Http\Requests\Productivity\ProductivityReferenceRequest;
use App\Http\Requests\Productivity\ProductivityWorkstationRequest;
use App\Http\Requests\Productivity\ProductivityDeleteRequest;
use App\Http\Requests\Productivity\ProductivityFindRequest;
use App\Http\Requests\Productivity\ProductivityFinishRequest;
use App\Http\Requests\Productivity\ProductivityRestoreRequest;
use App\Http\Requests\Productivity\ProductivityUpdateRequest;
use App\Http\Requests\Productivity\ProductivityStartRequest;
use App\Http\Requests\Productivity\ProductivityStopEndRequest;
use App\Http\Requests\Productivity\ProductivityStopRequest;
use App\Http\Resources\Productivity\ProductivityCollection;
use App\Http\Resources\Productivity\ProductivityResource;
use App\Models\Productivity;
use App\Models\ProductionOrder;
use App\Models\Rule;
use App\Models\Stop;
use App\Services\StandardRuleService;
use App\Services\WorkLogService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;

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
 *
 * @OA\Schema(
 *     schema="WorkInProgress",
 *     type="object",
 *     description="Trabajo en curso de un puesto: tiempos, paradas no programadas, la orden que se trabaja (con su referencia y regla) y sus destinos con la curva actual.",
 *     @OA\Property(property="start", type="string", example="2026-10-24 07:00:00", description="Inicio: la hora de inicio del turno o el fin del trabajo anterior del puesto"),
 *     @OA\Property(property="started_at", type="string", example="2026-10-24 07:12:40", description="Momento en que se tocó Iniciar"),
 *     @OA\Property(property="stops", type="array", @OA\Items(
 *         @OA\Property(property="stop_id", type="integer", example=480),
 *         @OA\Property(property="name", type="string", example="PARO MECANICO"),
 *         @OA\Property(property="start", type="string", example="2026-10-24 08:05:00"),
 *         @OA\Property(property="end", type="string", nullable=true, example=null)
 *     )),
 *     @OA\Property(property="open_stop", type="object", nullable=true, description="Parada no programada sin terminar"),
 *     @OA\Property(property="destinations", type="array", @OA\Items(
 *         @OA\Property(property="production_order_detail_id", type="integer", example=365),
 *         @OA\Property(property="destination", type="string", example="NACIONAL"),
 *         @OA\Property(property="reference", type="string", example="BO125478", description="Referencia (código del producto) de ese destino"),
 *         @OA\Property(property="sizes", type="array", @OA\Items(
 *             @OA\Property(property="size_id", type="integer", example=374),
 *             @OA\Property(property="name", type="string", example="04"),
 *             @OA\Property(property="quantity", type="integer", example=20)
 *         ))
 *     )),
 *     @OA\Property(property="preview", type="object", nullable=true, description="Resumen si se finalizara en este momento: start, end, people, duration_minutes, stop_minutes, available_minutes, dead_minutes, worked_minutes, standard_minutes, efficiency y stops (cada una con name, type, start, end y minutes, recortadas al trabajo)"),
 *     @OA\Property(property="production_order", type="object"),
 *     @OA\Property(property="reference", type="object"),
 *     @OA\Property(property="rule", type="object")
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
            // Con los desactivados, que se muestran para poder activarlos
            $productivities = Productivity::withTrashed()->with(['model'])
                ->when($request->filled('model_type') && $request->filled('model_id'),
                    function ($query) use ($request) {
                        return $query->where('model_type', $request->input('model_type'))
                            ->where('model_id', $request->integer('model_id'));
                    }
                )
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
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
            $productivity->settings = $request->settings();

            // El turno y sus paradas programadas (relación en model_has_subitems) se guardan juntos
            DB::transaction(function () use ($productivity) {
                $productivity->save();
                $productivity->stops()->sync(collect($productivity->settings->stops ?? [])->pluck('stop_id'));
            });

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
     *     description="Devuelve el turno con su estado (Por iniciar, En curso o Terminado), la meta por hora y las paradas programadas. Lo usa el modal de editar turno.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Turno", @OA\JsonContent(
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity", ref="#/components/schemas/Productivity")),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
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
                new ProductivityResource(Productivity::with('model')->findOrFail($id)),
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
     *     path="/productivity/work_logs/{id}",
     *     tags={"Productivities"},
     *     summary="Lo que se trabajó en un turno",
     *     description="Trabajos del turno ordenados por hora de inicio. Los finalizados traen referencia, lote, puesto, regla, inicio, fin, personas, minutos (duración, paradas, trabajado y estándar), eficiencia, paradas, unidades y sus destinos con las cantidades por talla y el estado del ingreso (Pendiente, Aprobado…). Los que están en curso traen status «En curso», su inicio y sus paradas, sin unidades ni eficiencia.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Trabajos del turno", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="productivity", ref="#/components/schemas/Productivity"),
     *             @OA\Property(property="work_logs", type="array", @OA\Items(
     *                 @OA\Property(property="status", type="string", example="Aprobado"),
     *                 @OA\Property(property="workstation", type="string", example="CORTE"),
     *                 @OA\Property(property="production_order", type="object", @OA\Property(property="code", type="string", example="PO260928005"), @OA\Property(property="lot", type="string", example="D")),
     *                 @OA\Property(property="reference", type="string", example="BO125478"),
     *                 @OA\Property(property="start", type="string", example="2026-10-24 07:00:00"),
     *                 @OA\Property(property="end", type="string", nullable=true, example="2026-10-24 13:38:33"),
     *                 @OA\Property(property="people", type="integer", example=4),
     *                 @OA\Property(property="efficiency", type="number", nullable=true, example=80),
     *                 @OA\Property(property="units", type="integer", nullable=true, example=770),
     *                 @OA\Property(property="destinations", type="array", @OA\Items(type="object"))
     *             ))
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function workLogs(ProductivityFindRequest $request, $id)
    {
        try {
            $productivity = Productivity::with('model')->findOrFail($id);

            return $this->successResponse(
                [
                    'productivity' => (new ProductivityResource($productivity))->toArray($request)['productivity'],
                    'work_logs' => app(WorkLogService::class)->shiftWorkLogs($productivity),
                ],
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
     *     description="Por iniciar: se puede cambiar fecha, horario, meta por hora y paradas programadas. En curso: solo la hora de fin (posterior a la hora actual; lo demás se ignora). Terminado o desactivado: 422. En todos los casos no puede cruzarse con otro turno del proceso y las paradas deben caber en el turno. Conserva el trabajo en curso de los puestos.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="date", type="string", format="date", example="2026-10-26"),
     *         @OA\Property(property="start_time", type="string", example="07:00"),
     *         @OA\Property(property="end_time", type="string", example="17:00"),
     *         @OA\Property(property="settings", type="object",
     *             @OA\Property(property="goal_hour", type="number", example=300),
     *             @OA\Property(property="stops", type="array", @OA\Items(
     *                 @OA\Property(property="stop_id", type="integer", example=478),
     *                 @OA\Property(property="start_time", type="string", example="12:00")
     *             ))
     *         )
     *     )),
     *     @OA\Response(response=200, description="Turno actualizado", @OA\JsonContent(
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity", ref="#/components/schemas/Productivity")),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(ProductivityUpdateRequest $request, $id)
    {
        try {
            $productivity = $request->shift();
            $productivity->date = $request->input('date');
            $productivity->start_time = $request->input('start_time');
            $productivity->end_time = $request->input('end_time');
            // Se conserva lo que no se edita en el formulario (el trabajo en curso de los puestos)
            $productivity->settings = array_merge(
                json_decode(json_encode($productivity->settings ?? []), true) ?: [],
                $request->settings()
            );

            // El turno y sus paradas programadas (relación en model_has_subitems) se guardan juntos
            DB::transaction(function () use ($productivity) {
                $productivity->save();
                $productivity->stops()->sync(collect($productivity->settings->stops ?? [])->pluck('stop_id'));
            });

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
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Turno desactivado", @OA\JsonContent(
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity", ref="#/components/schemas/Productivity")),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
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
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Turno activado", @OA\JsonContent(
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity", ref="#/components/schemas/Productivity")),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
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

    /**
     * @OA\Get(
     *     path="/productivity/workstation/{token}",
     *     tags={"Productivities"},
     *     summary="Resolver el QR de un puesto de trabajo",
     *     description="Lo llama la pantalla de Productividad al escanear el QR del puesto. Devuelve el puesto (proceso, subproceso u operación, siempre el nivel más bajo y activo), su proceso y el turno abierto de ese proceso en este momento. Responde 422 si el código no existe, si el puesto no es del nivel más bajo o si no hay turno abierto.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Identificador (uuid) del puesto de trabajo que lleva su QR",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9b2f6c1e-4a7d-4c3b-8e5f-2d1a0b9c8e7f")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Puesto de trabajo y turno abierto",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="workstation",
     *                     type="object",
     *                     @OA\Property(property="type", type="string", example="operation"),
     *                     @OA\Property(property="id", type="integer", example=215),
     *                     @OA\Property(property="name", type="string", example="Pegar bolsillo"),
     *                     @OA\Property(property="path", type="string", example="Confección › Delanteros › Pegar bolsillo")
     *                 ),
     *                 @OA\Property(
     *                     property="process",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=205),
     *                     @OA\Property(property="name", type="string", example="Confección")
     *                 ),
     *                 @OA\Property(
     *                     property="shift",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=12),
     *                     @OA\Property(property="date", type="string", example="2026-10-23"),
     *                     @OA\Property(property="start_time", type="string", example="06:00:00"),
     *                     @OA\Property(property="end_time", type="string", example="14:00:00"),
     *                     @OA\Property(property="settings", type="object", example={"goal_hour": 120}),
     *                     @OA\Property(property="stops", type="array", description="Paradas programadas del turno", @OA\Items(
     *                         @OA\Property(property="stop_id", type="integer", example=478),
     *                         @OA\Property(property="name", type="string", example="ALMUERZO"),
     *                         @OA\Property(property="type", type="string", example="programmed"),
     *                         @OA\Property(property="start", type="string", example="2026-10-24 12:00:00"),
     *                         @OA\Property(property="end", type="string", example="2026-10-24 12:30:00")
     *                     ))
     *                 ),
     *                 @OA\Property(property="work", ref="#/components/schemas/WorkInProgress", nullable=true),
     *                 @OA\Property(property="unprogrammed_stops", type="array", description="Paradas no programadas del catálogo", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=480),
     *                     @OA\Property(property="name", type="string", example="PARO MECANICO")
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function workstation(ProductivityWorkstationRequest $request, $token)
    {
        try {
            $workstation = $request->workstation();
            $process = $request->process();
            $shift = $request->shift();
            $service = app(WorkLogService::class);
            $work = $service->inProgress($shift, $workstation);

            $path = match (true) {
                $workstation instanceof \App\Models\Operation => [$process->name, $workstation->subprocess()->first()?->name, $workstation->name],
                $workstation instanceof \App\Models\Subprocess => [$process->name, $workstation->name],
                default => [$process->name],
            };

            return $this->successResponse(
                [
                    'workstation' => [
                        'type' => strtolower(class_basename($workstation)),
                        'id' => $workstation->id,
                        'name' => $workstation->name,
                        'path' => collect($path)->filter()->implode(' › '),
                    ],
                    'process' => [
                        'id' => $process->id,
                        'name' => $process->name,
                    ],
                    'shift' => [
                        'id' => $shift->id,
                        'date' => $shift->date?->format('Y-m-d'),
                        'start_time' => $shift->start_time,
                        'end_time' => $shift->end_time,
                        'settings' => ['goal_hour' => $shift->settings->goal_hour ?? null],
                        'stops' => $service->shiftStops($shift),
                    ],
                    // Trabajo en curso del puesto (al volver a escanear el QR la pantalla sigue donde iba), o null
                    'work' => $work ? $this->workPayload($work, $shift, $workstation, $process) : null,
                    // Paradas no programadas que se pueden registrar durante el trabajo
                    'unprogrammed_stops' => Stop::whereHas('stop_type', fn ($query) => $query->where('settings->has_defined_time_range', false))
                        ->orderBy('name')
                        ->get(['id', 'name'])
                        ->map(fn ($stop) => ['id' => $stop->id, 'name' => $stop->name])
                        ->values(),
                ],
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
     *     path="/productivity/workstation/{token}/reference/{uuid}",
     *     tags={"Productivities"},
     *     summary="Resolver la ficha técnica escaneada en un puesto de trabajo",
     *     description="Recibe el uuid del QR del puesto y el uuid de la orden de producción (sacado del QR de la ficha técnica impresa). Valida el puesto y el turno abierto como el endpoint del puesto, y devuelve la orden (código y lote), la referencia y la regla estándar que aplica: la del proceso del puesto, que coincide con su subproceso u operación si los tiene y con todos sus maestros, y que suma más puntos según los pesos configurados. Si ninguna aplica, `rule` es null.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid", example="9b2f6c1e-4a7d-4c3b-8e5f-2d1a0b9c8e7f")),
     *     @OA\Parameter(name="uuid", in="path", required=true, description="uuid de la orden de producción escaneada", @OA\Schema(type="string", format="uuid", example="3f1c2b4a-8d7e-4f6a-9b0c-1d2e3f4a5b6c")),
     *     @OA\Response(
     *         response=200,
     *         description="Referencia y regla estándar",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="production_order", type="object",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="code", type="string", example="00123"),
     *                     @OA\Property(property="lot", type="string", example="A")
     *                 ),
     *                 @OA\Property(property="reference", type="object",
     *                     @OA\Property(property="code", type="string", example="4521"),
     *                     @OA\Property(property="trademark", type="string", example="BLESS ORIGINAL"),
     *                     @OA\Property(property="group", type="string", example="DAMA"),
     *                     @OA\Property(property="category", type="string", example="CLASICO"),
     *                     @OA\Property(property="subcategory", type="string", example="JEAN SKINNY"),
     *                     @OA\Property(property="subgroup", type="string", example="TIRO ALTO"),
     *                     @OA\Property(property="collection", type="string", example="C52026 - COLOMBIA MODA")
     *                 ),
     *                 @OA\Property(property="rule", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=487),
     *                     @OA\Property(property="name", type="string", example="CORTE · MA BLESS ORIGINAL · GR DAMA"),
     *                     @OA\Property(property="time", type="number", example=60),
     *                     @OA\Property(property="time_unit", type="string", example="seg"),
     *                     @OA\Property(property="people", type="integer", example=4),
     *                     @OA\Property(property="points", type="integer", example=31)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function reference(ProductivityReferenceRequest $request, $token, $uuid)
    {
        try {
            $productionOrder = ProductionOrder::where('uuid', $uuid)->firstOrFail();

            return $this->successResponse(
                $this->referencePayload($productionOrder, $request->workstation(), $request->process()),
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
     *     path="/productivity/workstation/{token}/start",
     *     tags={"Productivities"},
     *     summary="Iniciar trabajo en un puesto",
     *     description="Inicia el trabajo del puesto con la orden escaneada. Por ahora solo en el primer proceso de la producción medido por lotes (Corte). El inicio es la hora de inicio del turno si es el primer trabajo del puesto en el turno o, si no, la hora en que terminó el anterior. El trabajo en curso queda en el turno (settings.work_in_progress) hasta finalizarlo. Responde 422 si el puesto ya tiene un trabajo en curso, si no hay regla estándar que aplique, si la orden no tiene curva por destino o si el lote ya se registró o se está trabajando en otro puesto.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"production_order"},
     *         @OA\Property(property="production_order", type="string", format="uuid", description="uuid de la orden de producción escaneada")
     *     )),
     *     @OA\Response(response=200, description="Trabajo en curso", @OA\JsonContent(
     *         @OA\Property(property="data", ref="#/components/schemas/WorkInProgress"),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function start(ProductivityStartRequest $request, $token)
    {
        try {
            $work = app(WorkLogService::class)->start($request->shift(), $request->workstation(), $request->productionOrder(), $request->rule());

            return $this->successResponse(
                $this->workPayload($work, $request->shift(), $request->workstation(), $request->process()),
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
     *     path="/productivity/workstation/{token}/stops",
     *     tags={"Productivities"},
     *     summary="Iniciar una parada no programada",
     *     description="Registra que el trabajo en curso del puesto se detuvo ahora por una parada no programada (paro mecánico, falta de insumos…). Solo una a la vez y nunca dentro de una parada programada del turno; las programadas se definen en el turno.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"stop_id"},
     *         @OA\Property(property="stop_id", type="integer", example=480, description="Parada de tipo no programada")
     *     )),
     *     @OA\Response(response=200, description="Trabajo en curso con la parada abierta", @OA\JsonContent(
     *         @OA\Property(property="data", ref="#/components/schemas/WorkInProgress"),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function stop(ProductivityStopRequest $request, $token)
    {
        try {
            $work = app(WorkLogService::class)->startStop($request->shift(), $request->workstation(), $request->stop());

            return $this->successResponse(
                $this->workPayload($work, $request->shift(), $request->workstation(), $request->process()),
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
     *     path="/productivity/workstation/{token}/stops/end",
     *     tags={"Productivities"},
     *     summary="Terminar la parada no programada en curso",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Trabajo en curso sin parada abierta", @OA\JsonContent(
     *         @OA\Property(property="data", ref="#/components/schemas/WorkInProgress"),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function stopEnd(ProductivityStopEndRequest $request, $token)
    {
        try {
            $work = app(WorkLogService::class)->endStop($request->shift(), $request->workstation());

            return $this->successResponse(
                $this->workPayload($work, $request->shift(), $request->workstation(), $request->process()),
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
     *     path="/productivity/workstation/{token}/finish",
     *     tags={"Productivities"},
     *     summary="Finalizar el trabajo en curso",
     *     description="Termina el trabajo ahora (cierra la parada abierta, si hay), calcula tiempos y eficiencia y crea un ingreso por destino: un detalle de movimiento cuyo origen es el turno, en estado Pendiente, en el movimiento del proceso y destino (se crea si no existe), con las unidades por talla y la regla enlazada. El proceso lo aprueba en Movimientos y la curva de la orden se ajusta.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"quantities"},
     *         @OA\Property(property="quantities", type="array", @OA\Items(
     *             @OA\Property(property="production_order_detail_id", type="integer", example=365, description="Destino de la orden"),
     *             @OA\Property(property="size_id", type="integer", example=374),
     *             @OA\Property(property="quantity", type="integer", example=20)
     *         ))
     *     )),
     *     @OA\Response(response=200, description="Resumen del trabajo", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="start", type="string", example="2026-10-24 07:00:00"),
     *             @OA\Property(property="end", type="string", example="2026-10-24 08:30:00"),
     *             @OA\Property(property="people", type="integer", example=4),
     *             @OA\Property(property="duration_minutes", type="number", example=90),
     *             @OA\Property(property="stop_minutes", type="number", example=15),
     *             @OA\Property(property="available_minutes", type="number", example=360),
     *             @OA\Property(property="dead_minutes", type="number", example=60),
     *             @OA\Property(property="worked_minutes", type="number", example=300),
     *             @OA\Property(property="standard_minutes", type="number", example=240),
     *             @OA\Property(property="efficiency", type="number", nullable=true, example=80),
     *             @OA\Property(property="stops", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="units", type="integer", example=345),
     *             @OA\Property(property="movement_details", type="array", @OA\Items(type="integer"))
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function finish(ProductivityFinishRequest $request, $token)
    {
        try {
            $summary = app(WorkLogService::class)->finish($request->shift(), $request->workstation(), $request->process(), $request->input('quantities'));

            return $this->successResponse(
                $summary,
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
     *     path="/productivity/stops",
     *     tags={"Productivities"},
     *     summary="Paradas del catálogo para productividad",
     *     description="Paradas programadas (con su duración en minutos, para agregarlas al turno) y no programadas (las que se registran durante un trabajo). Lo usa el modal de programar turno.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Paradas por tipo", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="programmed", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=478),
     *                 @OA\Property(property="name", type="string", example="ALMUERZO"),
     *                 @OA\Property(property="minutes", type="integer", example=30)
     *             )),
     *             @OA\Property(property="unprogrammed", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=480),
     *                 @OA\Property(property="name", type="string", example="PARO MECANICO")
     *             ))
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function stops()
    {
        try {
            $stops = Stop::with('stop_type')->orderBy('name')->get();

            $programmed = fn ($stop) => (bool) ($stop->stop_type->first()?->settings->has_defined_time_range ?? false);

            return $this->successResponse(
                [
                    'programmed' => $stops->filter($programmed)
                        ->map(fn ($stop) => ['id' => $stop->id, 'name' => $stop->name, 'minutes' => (int) ($stop->settings->range_time ?? 0)])
                        ->values(),
                    'unprogrammed' => $stops->reject($programmed)
                        ->map(fn ($stop) => ['id' => $stop->id, 'name' => $stop->name])
                        ->values(),
                ],
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
     * Orden, referencia y regla estándar que aplica en el puesto (la que se muestra al escanear la ficha).
     */
    private function referencePayload(ProductionOrder $productionOrder, $workstation, $process, ?Rule $rule = null): array
    {
        $productionOrder->loadMissing([
            'technical_sheet' => ['subgroup', 'collection', 'product' => ['trademark', 'subcategory']],
        ]);

        $product = $productionOrder->technical_sheet?->product;

        $best = app(StandardRuleService::class)->resolve($productionOrder, $workstation, $process);

        // Con un trabajo en curso se muestra la regla con la que se inició, aunque la configuración haya cambiado
        if ($rule && ($best['rule']->id ?? null) !== $rule->id) {
            $best = ['rule' => $rule, 'points' => null, 'criteria' => []];
        }

        return [
            'production_order' => [
                'id' => $productionOrder->id,
                'uuid' => $productionOrder->uuid,
                'code' => $productionOrder->consecutive,
                'lot' => $productionOrder->cut,
            ],
            'reference' => [
                'code' => $product?->code,
                'trademark' => $product?->trademark?->name,
                'group' => $product?->trademark?->group()->first()?->name,
                'category' => $product?->subcategory?->category()->first()?->name,
                'subcategory' => $product?->subcategory?->name,
                'subgroup' => $productionOrder->technical_sheet?->subgroup?->name,
                // Código de la colección (settings.code) y su nombre: "C52026 - COLOMBIA MODA"
                'collection' => collect([
                    $productionOrder->technical_sheet?->collection?->settings?->code,
                    $productionOrder->technical_sheet?->collection?->name,
                ])->filter()->implode(' - ') ?: null,
            ],
            'rule' => $best ? [
                'id' => $best['rule']->id,
                'name' => $best['rule']->name,
                'time' => $best['rule']->settings->time ?? null,
                'time_unit' => $best['rule']->settings->time_unit ?? null,
                'people' => $best['rule']->settings->people ?? null,
                'points' => $best['points'],
                'criteria' => $best['criteria'],
            ] : null,
        ];
    }

    /**
     * Trabajo en curso del puesto: tiempos, paradas no programadas, la referencia que se trabaja, sus destinos con
     * la curva (para digitar las unidades al finalizar) y el resumen si se finalizara ahora.
     */
    private function workPayload(array $work, Productivity $shift, $workstation, $process): array
    {
        $productionOrder = ProductionOrder::findOrFail($work['production_order_id']);
        $rule = Rule::find($work['rule_id']);

        return array_merge(
            [
                'start' => $work['start'],
                'started_at' => $work['started_at'] ?? null,
                'stops' => $work['stops'] ?? [],
                'open_stop' => collect($work['stops'] ?? [])->first(fn ($stop) => empty($stop['end'])),
                'destinations' => app(WorkLogService::class)->destinations($productionOrder),
                // Resumen si se finalizara ahora (inicio, fin, duración, personas, paradas y tiempos), para el modal
                // de finalizar; al finalizar se recalcula con la hora real
                'preview' => $rule ? app(WorkLogService::class)->summary($shift, $work, now(), $rule) : null,
            ],
            $this->referencePayload($productionOrder, $workstation, $process, $rule)
        );
    }
}
