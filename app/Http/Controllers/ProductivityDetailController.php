<?php

namespace App\Http\Controllers;

use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailAllRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailDeleteRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailFinishRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailMinusRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailMovementRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailWorkstationMineRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailOptionsRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailPlusRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailReferenceRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailReprocessDeleteRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailReprocessAllRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailReprocessRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailScheduleRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailStartRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailStopEndRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailStopRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailStoreRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailTechnicalSheetRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailUpdateRequest;
use App\Http\Requests\Productivity\ProductivityDetail\ProductivityDetailWorkstationRequest;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailCollection;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailFinishResource;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailMovementResource;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailWorkstationMineResource;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailOptionsResource;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailReferenceResource;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailReprocessResource;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailResource;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailScheduledResource;
use App\Http\Resources\Productivity\ProductivityDetail\ProductivityDetailWorkstationResource;
use App\Models\Employee;
use App\Models\Movement;
use App\Models\Operation;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\Productivity;
use App\Models\ProductivityDetail;
use App\Models\Stop;
use App\Models\Subprocess;
use App\Services\ProductivityDetailService;
use App\Services\StandardRuleService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @OA\Tag(
 *     name="Productivities › Details",
 *     description="Endpoints para gestionar los registros de trabajo de los turnos"
 * )
 *
 * @OA\Tag(
 *     name="Productivities › Details › Workstation",
 *     description="Endpoints para gestionar el trabajo en la estación de cada puesto"
 * )
 *
 * @OA\Schema(
 *     schema="ProductivityDetailInProgress",
 *     type="object",
 *     description="Registro de trabajo en curso de un puesto (productivity_details): tiempos, empleados, paradas, la orden que se trabaja (con su referencia y regla) y, si el puesto crea ingresos, sus destinos con la curva actual.",
 *
 *     @OA\Property(property="id", type="integer", example=12, description="Registro de trabajo"),
 *     @OA\Property(property="start", type="string", example="2026-10-24 07:00:00", description="Inicio oficial: la hora programada; sin programación, la hora de inicio del turno o el fin del registro anterior del puesto. La hora real del escaneo queda en settings.scanned_at"),
 *     @OA\Property(property="scanned_at", type="string", example="2026-10-24 07:12:40", description="Hora real en que se inició"),
 *     @OA\Property(property="shift_end", type="string", example="2026-10-24 17:00:00", description="Fin del turno: si se finaliza después, el fin oficial es este"),
 *     @OA\Property(property="employees", type="array", @OA\Items(
 *         @OA\Property(property="id", type="integer", example=15),
 *         @OA\Property(property="name", type="string", example="ANA PÉREZ"),
 *         @OA\Property(property="document", type="string", example="1090123456")
 *     )),
 *     @OA\Property(property="stops", type="array", description="Paradas registradas (productivity_detail_models)", @OA\Items(
 *         @OA\Property(property="id", type="integer", example=40),
 *         @OA\Property(property="stop_id", type="integer", example=480),
 *         @OA\Property(property="name", type="string", example="PARO MECANICO"),
 *         @OA\Property(property="type", type="string", enum={"programmed", "unprogrammed"}, example="unprogrammed"),
 *         @OA\Property(property="start", type="string", example="2026-10-24 08:05:00"),
 *         @OA\Property(property="end", type="string", nullable=true, example=null, description="La programada trae su fin (inicio + duración del catálogo); la no programada abierta, null")
 *     )),
 *     @OA\Property(property="open_stop", type="object", nullable=true, description="Parada en curso: no programada sin fin o programada que aún no termina"),
 *     @OA\Property(property="creates_ingress", type="boolean", example=false, description="Al finalizar pide unidades y crea los ingresos (último puesto de la cadena del primer proceso)"),
 *     @OA\Property(property="measurement", type="string", enum={"lots", "units"}, example="units"),
 *     @OA\Property(property="produced", type="integer", example=35, description="Unidades que lleva este registro con el +1"),
 *     @OA\Property(property="counters", type="object", nullable=true, description="Solo por unidades: contadores del lote en el proceso",
 *         @OA\Property(property="details", type="array", @OA\Items(
 *             @OA\Property(property="movement_detail_id", type="integer", example=200),
 *             @OA\Property(property="destination", type="string", example="NACIONAL"),
 *             @OA\Property(property="received", type="integer", example=700),
 *             @OA\Property(property="produced", type="integer", example=520)
 *         )),
 *         @OA\Property(property="received", type="integer", example=1100),
 *         @OA\Property(property="produced", type="integer", example=520),
 *         @OA\Property(property="current", type="integer", nullable=true, example=200, description="Detalle que se está llenando; null si el lote está completo")
 *     ),
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
 *     @OA\Property(property="preview", type="object", nullable=true, description="Resumen si se finalizara en este momento (con el fin oficial): start, end, people, duration_minutes, stop_minutes, available_minutes, dead_minutes, worked_minutes, standard_minutes, efficiency y stops (cada una con name, type, start, end y minutes, recortadas al trabajo)"),
 *     @OA\Property(property="production_order", type="object"),
 *     @OA\Property(property="reference", type="object"),
 *     @OA\Property(property="rule", type="object")
 * )
 */
class ProductivityDetailController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/productivity/details/all/{productivity_id}",
     *     tags={"Productivities › Details"},
     *     summary="Registros de trabajo de un turno",
     *     description="Registros del turno ordenados por hora de inicio (ojito de la tabla de turnos). Los finalizados traen referencia, lote, puesto, regla, empleados, inicio oficial, hora de escaneo, fin, personas, minutos (duración, paradas, trabajado y estándar), eficiencia, paradas, unidades (ingresos que creó en Corte o lo que sumó con el +1 en procesos por unidades) y, si creó ingresos, sus destinos con las cantidades por talla y el estado del ingreso. Los que están en curso traen status «En curso», su inicio y sus paradas. Los puestos que no crean ingresos ni cuentan unidades traen status «Terminado» y `units` null.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="productivity_id", in="path", required=true, description="Turno", @OA\Schema(type="integer", example=1)),
     *
     *     @OA\Response(response=200, description="Registros del turno", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="productivity_details", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         ),
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
    public function all(ProductivityDetailAllRequest $request, $productivity_id)
    {
        try {
            $details = ProductivityDetail::with(['productivity', 'model', 'rule', 'stops', 'employees' => ['person'], 'production_order' => ['technical_sheet' => ['product', 'garment_type']], 'movement_details' => ['movement' => ['production_order_detail' => ['model']], 'movement_detail_quantities' => ['size']]])
                ->where('productivity_id', $productivity_id)
                ->orderBy('start');

            $details = $details->paginate($request->integer('per_page', max($details->count(), 1)));
            $details->getCollection()->loadMorph('model', [Operation::class => ['subprocess']]);

            return $this->successResponse(
                new ProductivityDetailCollection($details),
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
     *     path="/productivity/details/technical_sheet/{technical_sheet_id}",
     *     tags={"Productivities › Details"},
     *     summary="Productividad de una referencia",
     *     description="Registros de trabajo iniciados de todos los lotes de la ficha técnica, de todos los turnos y procesos, crudos como en el listado del turno (con el turno y su proceso). La pantalla arma la línea por lote y proceso y calcula tiempos y eficiencia.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="technical_sheet_id", in="path", required=true, @OA\Schema(type="integer", example=12)),
     *
     *     @OA\Response(response=200, description="Registros de trabajo de la referencia", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity_details", type="array", @OA\Items(type="object"))),
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
    public function technical_sheet(ProductivityDetailTechnicalSheetRequest $request, $technical_sheet_id)
    {
        try {
            $details = ProductivityDetail::with(['productivity' => ['model'], 'model', 'rule', 'stops', 'employees' => ['person'], 'production_order' => ['technical_sheet' => ['product', 'garment_type']], 'movement_details' => ['movement' => ['production_order_detail' => ['model']], 'movement_detail_quantities' => ['size']]])
                ->whereNotNull('settings->scanned_at')
                ->whereHas('production_order', fn ($production_order) => $production_order->where('technical_sheet_id', $technical_sheet_id))
                ->orderBy('start');

            $details = $details->paginate($request->integer('per_page', max($details->count(), 1)));
            $details->getCollection()->loadMorph('model', [Operation::class => ['subprocess']]);

            return $this->successResponse(
                new ProductivityDetailCollection($details),
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
     *     path="/productivity/details/movement/{movement_id}",
     *     tags={"Productivities › Details"},
     *     summary="Productividad de un movimiento",
     *     description="Registros de trabajo iniciados del lote en los puestos del proceso del movimiento (todos los turnos), con las unidades que le tocaron a ese destino: por unidades, los +1 contados contra sus ingresos aprobados (y other_units, lo del mismo registro en otros destinos); en Corte, los ingresos que creó el registro para el destino; si no, null. Trae lo recibido por el destino (ingresos aprobados).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="movement_id", in="path", required=true, @OA\Schema(type="integer", example=12)),
     *
     *     @OA\Response(response=200, description="Productividad", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="measurement", type="string", example="units", nullable=true),
     *             @OA\Property(property="received", type="integer", example=400),
     *             @OA\Property(property="productivity_details", type="array", @OA\Items(type="object"))
     *         ),
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
    public function movement(ProductivityDetailMovementRequest $request, $movement_id)
    {
        try {
            $movement = Movement::with(['model', 'production_order_detail', 'movement_details_input' => ['movement_detail_quantities']])->find($movement_id);
            $workstations = app(ProductivityDetailService::class)->workstations($movement->model);

            $productivity_details = ProductivityDetail::with(['model', 'rule', 'stops', 'productivity', 'employees' => ['person'], 'production_order' => ['technical_sheet'], 'movement_details' => ['movement_detail_quantities']])
                ->where('production_order_id', $movement->production_order_detail->production_order_id)
                ->whereNotNull('settings->scanned_at')
                ->where(fn ($query) => $workstations->each(fn ($workstation) => $query->orWhere(fn ($same) => $same
                    ->where('model_type', $workstation['model_type'])
                    ->where('model_id', $workstation['model_id']))))
                ->orderBy('start')
                ->get()
                // El subproceso de las operaciones: de ahí heredan el multiplicador del tiempo (botones de la ficha)
                ->loadMorph('model', [Operation::class => ['subprocess']]);

            return $this->successResponse(
                new ProductivityDetailMovementResource([
                    'movement' => $movement,
                    'productivity_details' => $productivity_details,
                ]),
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
     *     path="/productivity/details/workstation/{token}",
     *     tags={"Productivities › Details › Workstation"},
     *     summary="Resolver el QR de un puesto de trabajo",
     *     description="Lo llama la pantalla de Productividad al escanear el QR del puesto. Devuelve el puesto crudo (proceso, subproceso u operación, siempre el nivel más bajo) con sus padres y los turnos de su proceso (de hoy en adelante y el del registro en curso), y el registro en curso del puesto si lo tiene. En references van los lotes que se pueden trabajar en el puesto, para elegirlos sin escanear: primero los programados y sin iniciar en el turno abierto (con start, su hora) y luego los demás (start null). La pantalla decide con qué turno se trabaja. Responde 422 si el código no existe o si el puesto no es del nivel más bajo.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Identificador (uuid) del puesto de trabajo que lleva su QR",
     *         required=true,
     *
     *         @OA\Schema(type="string", format="uuid", example="9b2f6c1e-4a7d-4c3b-8e5f-2d1a0b9c8e7f")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Puesto de trabajo",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="workstation", type="object", description="Puesto crudo con subprocess.process.productivities (operación), process.productivities (subproceso) o productivities (proceso)"),
     *                 @OA\Property(property="productivity_detail", ref="#/components/schemas/ProductivityDetailInProgress", nullable=true)
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function workstation_mine(ProductivityDetailWorkstationMineRequest $request)
    {
        try {
            $employee = $request->user()->employee;

            return $this->successResponse(
                new ProductivityDetailWorkstationMineResource($employee ? app(ProductivityDetailService::class)->employeeWorkstations($employee) : collect()),
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

    public function workstation(ProductivityDetailWorkstationRequest $request, $token)
    {
        try {
            $workstation = null;

            foreach ([Operation::class, Subprocess::class, Process::class] as $model) {
                $workstation ??= $model::where('settings->uuid', $token)->first();
            }

            // En un puesto programado pueden trabajar varios grupos: la pantalla elige su trabajo de la lista
            $service = app(ProductivityDetailService::class);
            $process = $service->processOfWorkstation($workstation);
            $groups = $process && $service->programmable($process, $workstation);
            $productivity_details = $groups ? $service->inProgressRows($workstation) : collect();
            $productivity_detail = $groups && ! $request->integer('productivity_detail_id')
                ? null
                : $service->inProgress($workstation, $request->integer('productivity_detail_id'));
            $productivity_ids = $productivity_details->pluck('productivity_id')->push($productivity_detail?->productivity_id)->filter();

            $shifts = fn ($query) => $query
                ->where(fn ($dates) => $dates->whereDate('date', '>=', today())
                    ->orWhereIn('id', $productivity_ids))
                ->orderBy('date')
                ->orderBy('start_time');

            $workstation->load(match (true) {
                $workstation instanceof Operation => ['subprocess' => ['process' => ['productivities' => $shifts]]],
                $workstation instanceof Subprocess => ['process' => ['productivities' => $shifts]],
                default => ['productivities' => $shifts],
            });

            return $this->successResponse(
                new ProductivityDetailWorkstationResource([
                    'workstation' => $workstation,
                    'productivity_detail' => $productivity_detail,
                    'productivity_details' => $productivity_details,
                    'references' => $service->workstationReferences($workstation),
                ]),
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
     *     path="/productivity/details/workstation/{token}/reference/{uuid}",
     *     tags={"Productivities › Details › Workstation"},
     *     summary="Resolver la ficha técnica escaneada en un puesto de trabajo",
     *     description="Recibe el uuid del QR del puesto y el uuid de la orden de producción (sacado del QR de la ficha técnica impresa). Valida el puesto y el turno abierto como el endpoint del puesto, y devuelve la orden (código y lote), la referencia y la regla estándar que aplica: la del proceso del puesto, que coincide con su subproceso u operación si los tiene y con todos sus maestros, y que suma más puntos según los pesos configurados. Si ninguna aplica, `rule` es null.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid", example="9b2f6c1e-4a7d-4c3b-8e5f-2d1a0b9c8e7f")),
     *     @OA\Parameter(name="uuid", in="path", required=true, description="uuid de la orden de producción escaneada", @OA\Schema(type="string", format="uuid", example="3f1c2b4a-8d7e-4f6a-9b0c-1d2e3f4a5b6c")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Referencia y regla estándar",
     *
     *         @OA\JsonContent(
     *
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
     *                 ),
     *                 @OA\Property(property="employees", type="array", description="Empleados que se pueden elegir: asignados al puesto, activos y sin otro registro en curso", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=15),
     *                     @OA\Property(property="name", type="string", example="ANA PÉREZ"),
     *                     @OA\Property(property="document", type="string", example="1090123456")
     *                 )),
     *                 @OA\Property(property="creates_ingress", type="boolean", example=true),
     *                 @OA\Property(property="sequence", type="object", description="Cadena en secuencia: el lote debe haber terminado en los puestos anteriores (vacío si el puesto es el primero o su padre va en paralelo)",
     *                     @OA\Property(property="ready", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", nullable=true, example="El lote BO001-A aún se está trabajando en EXTENDIDO."),
     *                     @OA\Property(property="previous", type="array", @OA\Items(
     *                         @OA\Property(property="name", type="string", example="EXTENDIDO"),
     *                         @OA\Property(property="status", type="string", enum={"finished", "in_progress", "missing"}, example="finished"),
     *                         @OA\Property(property="end", type="string", nullable=true, example="2026-10-26 07:40:00")
     *                     )),
     *                     @OA\Property(property="previous_end", type="string", nullable=true, example="2026-10-26 07:40:00")
     *                 ),
     *                 @OA\Property(property="registered", type="object", nullable=true, description="Proceso por lotes: el lote ya se trabajó en este puesto (no se puede volver a iniciar). end null si sigue en curso",
     *                     @OA\Property(property="start", type="string", example="2026-10-27 09:00:00"),
     *                     @OA\Property(property="end", type="string", nullable=true, example="2026-10-27 10:00:00")
     *                 ),
     *                 @OA\Property(property="start", type="string", nullable=true, example="2026-10-26 07:40:00", description="Hora oficial a la que iniciaría el trabajo si se inicia ahora")
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function reference(ProductivityDetailReferenceRequest $request, $token, $uuid)
    {
        try {
            $context = app(ProductivityDetailService::class)->workstationContext($token);

            return $this->successResponse(
                new ProductivityDetailReferenceResource([
                    'production_order' => ProductionOrder::where('uuid', $uuid)->firstOrFail(),
                    'workstation' => $context['workstation'],
                    'process' => $context['process'],
                    'shift' => $context['shift'],
                ]),
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
     *     path="/productivity/details/workstation/{token}/start",
     *     tags={"Productivities › Details › Workstation"},
     *     summary="Iniciar trabajo en un puesto",
     *     description="Retoma el registro programado del lote en el puesto dentro del turno (422 si no está programado o si aún no llega su hora: no se empieza antes de la hora programada, salvo en la cadena de Corte, donde se puede adelantar la siguiente del orden programado del puesto si el lote ya pasó por el puesto anterior; adelantada, su inicio oficial es apenas quedó libre el puesto y el lote soltó el puesto anterior) y le pone los empleados que vinieron; solo los puestos que no se programan crean el registro al escanear. En los procesos en paralelo el inicio oficial es la hora real del escaneo; si no, es lo más tardío entre la hora programada, la hora de inicio del turno, el fin del registro anterior del puesto en el turno y, en una cadena en secuencia, el fin del lote en el puesto anterior; la hora real queda en settings.scanned_at. En una cadena en secuencia el lote debe haber terminado en los puestos anteriores. Responde 422 si el puesto ya tiene un trabajo en curso, si el turno ya terminó, si el primer proceso trabaja sus hijos en paralelo, si no hay regla estándar que aplique, si el puesto crea ingresos y la orden no tiene curva por destino, si el lote ya se trabajó en el puesto o si la regla no tiene capacidad para las personas del puesto (por lotes, una capacidad con ese número exacto; por unidades, la de 1 persona multiplicada).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"production_order", "employees"},
     *
     *         @OA\Property(property="production_order", type="string", format="uuid", description="uuid de la orden de producción escaneada"),
     *         @OA\Property(property="employees", type="array", description="Empleados que trabajan; si está programado, el grupo programado (la capacidad de la regla sale de cuántos son)", @OA\Items(type="integer", example=15)),
     *         @OA\Property(property="replaces", type="object", description="Reemplazos de programados ocupados en otro trabajo en curso, uno por uno: { id del que entra: id del programado }. Queda en el settings del empleado como replaces", example={"31": 29})
     *     )),
     *
     *     @OA\Response(response=201, description="Trabajo en curso", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity_detail", ref="#/components/schemas/ProductivityDetailInProgress")),
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
    public function start(ProductivityDetailStartRequest $request, $token)
    {
        try {
            $service = app(ProductivityDetailService::class);
            $context = $service->workstationContext($token);
            $workstation = $context['workstation'];
            $process = $context['process'];
            $shift = $context['shift'];
            $production_order = ProductionOrder::where('uuid', $request->input('production_order'))->first();
            $employees = collect($request->input('employees'))->map(fn ($id) => (int) $id);

            $scheduled = $service->schedulable($process, $workstation) ? $service->scheduledDetail($shift, $workstation, $production_order, $employees) : null;
            // El mismo grupo con otros puestos del lote a la vez (PARCHE + RELOJERA): se inician juntos
            $companions = $scheduled ? $service->companionsToStart($scheduled, $process) : collect();
            // A quién reemplazó cada persona (el programado que estaba ocupado en otro trabajo)
            $replaces = collect((array) $request->input('replaces'))->mapWithKeys(fn ($old, $new) => [(int) $new => (int) $old]);

            $productivity_detail = $this->startRow($service, $shift, $process, $workstation, $production_order, $scheduled, $employees, $replaces);

            foreach ($companions as $companion) {
                $this->startRow($service, $shift, $process, $companion->model, $production_order, $companion, $employees, $replaces);
            }

            $service->refreshWaitingStops($shift, $production_order, $process);

            return $this->successResponse(
                new ProductivityDetailResource($productivity_detail->load(['productivity', 'stops', 'employees' => ['person']])),
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
     * @OA\Get(
     *     path="/productivity/details/options/{productivity_id}",
     *     tags={"Productivities › Details"},
     *     summary="Opciones para programar un turno",
     *     description="Solo procesos por unidades. `busy`: empleados ocupados en otros turnos. Los puestos salen del `model` de `productivity/find`; los lotes y su `estimate` (unidades por hacer y tiempo por unidad de la regla) salen de `production_orders/all` con `process_id`, `model_type` y `model_id`.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="productivity_id", in="path", required=true, description="Turno", @OA\Schema(type="integer", example=10)),
     *
     *     @OA\Response(response=200, description="Opciones", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="busy", type="array", @OA\Items(type="object"))
     *         ),
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
    /**
     * Inicia el registro del lote en el puesto: el programado o uno nuevo, con su hora oficial y su grupo.
     */
    protected function startRow(ProductivityDetailService $service, Productivity $shift, Process $process, Model $workstation, ProductionOrder $production_order, ?ProductivityDetail $scheduled, Collection $employees, Collection $replaces): ProductivityDetail
    {
        $rule = $scheduled?->rule ?? app(StandardRuleService::class)->resolve($production_order, $workstation, $process)['rule'];

        $productivity_detail = $scheduled ?? new ProductivityDetail;
        $productivity_detail->productivity_id = $shift->id;
        $productivity_detail->model_type = $workstation::class;
        $productivity_detail->model_id = $workstation->id;
        $productivity_detail->production_order_id = $production_order->id;
        $productivity_detail->rule_id = $rule->id;
        $limit = $service->unitsLimit($shift, $production_order, $workstation, $process, $scheduled ? $service->scheduledStart($scheduled) : now());
        $start = $service->startTime($shift, $workstation, $production_order, $service->programmable($process, $workstation) ? $scheduled : null, $employees);
        // Por lote (Corte) no empieza antes de su hora programada, salvo que se adelante
        $productivity_detail->start = match (true) {
            // Sin programación en el puesto: empieza cuando realmente lo escanean
            ! $scheduled && $service->schedulable($process, $workstation) => now(),
            $scheduled && ! $service->programmable($process, $workstation) => $service->lotStart($start, $scheduled, $shift),
            default => $start,
        };
        // Por lotes se guarda la capacidad de la regla para esas personas (la regla puede cambiar después); por
        // unidades no hace falta: el tiempo por unidad sale de la regla asociada
        $productivity_detail->settings = array_merge(
            (array) $productivity_detail->settings,
            ['scanned_at' => now()->format('Y-m-d H:i:s')],
            // El puesto programado antes no ha hecho nada: este toma el lote, salvo que vaya antes en la cadena (ahí espera)
            $limit && $limit['limit'] < 1 && ! $limit['chain'] ? ['took_lot' => true] : [],
            $service->measuredByUnits($workstation) ? [] : ['capacity' => $service->capacity($rule, $workstation, $employees->count())]
        );
        $productivity_detail->save();
        $productivity_detail->models()->where('model_type', Employee::class)->delete();

        $own = $service->eligibleEmployees($workstation, $production_order->id)->pluck('id');
        $borrowable = $service->borrowableEmployees($workstation, $process, $production_order->id)->keyBy('id');

        foreach ($employees as $employee_id) {
            $productivity_detail->models()->create([
                'model_type' => Employee::class,
                'model_id' => $employee_id,
                'settings' => array_merge(
                    $own->contains($employee_id) ? [] : ['borrowed_from' => $borrowable[$employee_id]['workstation'] ?? null],
                    $replaces->has($employee_id) ? ['replaces' => $replaces[$employee_id]] : [],
                ),
            ]);
        }

        return $productivity_detail;
    }

    public function options(ProductivityDetailOptionsRequest $request, $productivity_id)
    {
        try {
            return $this->successResponse(
                new ProductivityDetailOptionsResource(Productivity::find($productivity_id)),
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
     *     path="/productivity/details/store",
     *     tags={"Productivities › Details"},
     *     summary="Programar un registro de trabajo",
     *     description="Solo procesos por unidades y antes de que inicie el turno. Crea el registro (sin settings.scanned_at = programado) con el lote, el puesto, la regla, el inicio y los empleados; crea en 0 los contadores que falten y reparte las unidades por hacer del lote entre sus registros del turno en partes iguales y enteras (settings.scheduled: start, end, units = tope, people, unit_time, time_unit, minutes).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"productivity_id", "model_type", "model_id", "production_order_id", "start", "employees"},
     *
     *         @OA\Property(property="productivity_id", type="integer", example=10),
     *         @OA\Property(property="model_type", type="string", example="App\\Models\\Operation"),
     *         @OA\Property(property="model_id", type="integer", example=343),
     *         @OA\Property(property="production_order_id", type="integer", example=61),
     *         @OA\Property(property="start", type="string", example="07:00"),
     *         @OA\Property(property="employees", type="array", @OA\Items(type="integer", example=15))
     *     )),
     *
     *     @OA\Response(response=201, description="Programado", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object"),
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
    public function store(ProductivityDetailStoreRequest $request)
    {
        try {
            $shift = Productivity::find($request->integer('productivity_id'));
            $production_order = ProductionOrder::find($request->integer('production_order_id'));
            $service = app(ProductivityDetailService::class);

            $productivity_detail = new ProductivityDetail;
            $productivity_detail->productivity_id = $shift->id;
            $productivity_detail->model_type = $request->input('model_type');
            $productivity_detail->model_id = $request->integer('model_id');
            $productivity_detail->production_order_id = $production_order->id;
            $productivity_detail->rule_id = $service->unitTime($production_order, $productivity_detail->model, $shift->model)['rule']->id;
            $productivity_detail->start = $shift->date->format('Y-m-d').' '.$request->input('start');
            // Cantidad que multiplica el tiempo (golpes por prenda…), solo en los puestos que la piden
            $productivity_detail->settings = ($service->multiplierOf($productivity_detail->model)['mode'] ?? null) === 'manual'
                ? ['multiplier' => (float) $request->input('multiplier')]
                : [];
            // Tandas: las unidades las digita quien programa; el reparto las conserva
            if ($service->typedUnits($shift->model, $productivity_detail->model)) {
                $productivity_detail->settings = [...(array) $productivity_detail->settings, 'scheduled' => ['units' => $request->integer('units')]];
            }
            $productivity_detail->save();

            $borrowed = $service->workstations($shift->model)
                ->reject(fn ($item) => $item['model_type'] === $productivity_detail->model_type && $item['model_id'] === $productivity_detail->model_id)
                ->flatMap(fn ($item) => collect($item['employees'])->map(fn ($employee) => [$employee['id'], $item['name']]))
                ->mapWithKeys(fn ($pair) => [$pair[0] => $pair[1]]);

            foreach ($request->input('employees') as $employee_id) {
                $productivity_detail->models()->create([
                    'model_type' => Employee::class,
                    'model_id' => (int) $employee_id,
                    'settings' => isset($borrowed[$employee_id]) ? ['borrowed_from' => $borrowed[$employee_id]] : [],
                ]);
            }

            $service->reshare($shift, $production_order, $productivity_detail->model);

            if ($service->schedulesByLot($shift->model, $productivity_detail->model)) {
                $service->scheduleLot($productivity_detail, $request->input('end'));
            }

            return $this->successResponse(
                new ProductivityDetailScheduledResource($productivity_detail->refresh()->load(['model', 'employees' => ['person'], 'production_order' => ['technical_sheet' => ['product']], 'rule'])),
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
     * @OA\Post(
     *     path="/productivity/details/schedule/{productivity_id}",
     *     tags={"Productivities › Details"},
     *     summary="Programar en cadena un turno de Corte",
     *     description="Para el primer proceso con sus subprocesos en secuencia (Corte): recibe las referencias en el orden en que se trabajan, cada una con los puestos que el líder le quitó (`removed`, «model_type:model_id»; desde el primero quitado no sigue a los siguientes) y sus empleados por puesto (`employees`: los elige el líder; sin personas la fila no tiene tiempo y no se puede guardar). Con `preview: true` solo calcula y responde `{rows, errors}` (lo iniciado y lo calculado) sin guardar. Sin `preview` quita lo programado sin iniciar del turno y lo vuelve a crear: cada lote entra a cada puesto cuando lo soltó el puesto anterior y el puesto terminó el lote anterior, con el tiempo de la regla para esas personas (settings.scheduled: start, end, minutes, units). Lo ya iniciado se queda. Responde 422 si falta una regla o su capacidad, si un puesto no tiene empleados, si una referencia no alcanza a terminar en el turno o si un empleado está ocupado en esa franja.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="productivity_id", in="path", required=true, @OA\Schema(type="integer", example=10)),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"production_orders"},
     *
     *         @OA\Property(property="preview", type="boolean", example=true, description="Solo calcular, sin guardar"),
     *         @OA\Property(property="production_orders", type="array", description="En el orden en que se trabajan", @OA\Items(
     *             @OA\Property(property="id", type="integer", example=61),
     *             @OA\Property(property="removed", type="array", @OA\Items(type="string", example="App\Models\Subprocess:604")),
     *             @OA\Property(property="employees", type="object", example={"App\Models\Subprocess:602": {15, 16}})
     *         ))
     *     )),
     *
     *     @OA\Response(response=200, description="Programado", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object"),
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
    public function schedule_store(ProductivityDetailScheduleRequest $request, $productivity_id)
    {
        try {
            $shift = Productivity::find($productivity_id);
            $service = app(ProductivityDetailService::class);
            $plan = $service->chainPlan($shift, $request->input('production_orders'));

            if ($request->boolean('preview')) {
                return $this->successResponse($service->chainPreview($shift, $plan), $this->getMessage('Success'), 200);
            }

            $shift->productivity_details()->scheduled()->get()->each(fn (ProductivityDetail $row) => $row->delete());

            foreach ($plan['rows'] as $row) {
                $productivity_detail = new ProductivityDetail;
                $productivity_detail->productivity_id = $shift->id;
                $productivity_detail->model_type = $row['workstation']::class;
                $productivity_detail->model_id = $row['workstation']->id;
                $productivity_detail->production_order_id = $row['production_order']->id;
                $productivity_detail->rule_id = $row['rule']->id;
                $productivity_detail->start = $row['start'];
                $productivity_detail->settings = [
                    'scheduled' => [
                        'start' => $row['start']->format('Y-m-d H:i:s'),
                        'units' => $service->lotUnitsIn($row['production_order'], $shift->model),
                        'minutes' => round($row['minutes'], 2),
                        'end' => $row['end']->format('Y-m-d H:i:s'),
                    ],
                ];
                $productivity_detail->save();

                foreach ($row['employees'] as $employee_id) {
                    $productivity_detail->models()->create([
                        'model_type' => Employee::class,
                        'model_id' => $employee_id,
                        'settings' => isset($row['borrowed'][$employee_id]) ? ['borrowed_from' => $row['borrowed'][$employee_id]] : [],
                    ]);
                }
            }

            return $this->successResponse(
                ProductivityDetailScheduledResource::collection($shift->productivity_details()->with(['model', 'employees' => ['person'], 'production_order' => ['technical_sheet' => ['product']], 'rule'])->orderBy('start')->get()),
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
     *     path="/productivity/details/update/{id}",
     *     tags={"Productivities › Details"},
     *     summary="Editar un registro programado",
     *     description="Lo mismo que programar, sin contar el registro mismo. Solo antes de que inicie el turno y si la estación no lo ha iniciado. Rehace el reparto del lote (y del lote anterior si cambió).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *
     *     @OA\Response(response=200, description="Actualizado", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object"),
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
    public function update(ProductivityDetailUpdateRequest $request, $id)
    {
        try {
            $productivity_detail = ProductivityDetail::with('productivity')->findOrFail($id);
            $shift = $productivity_detail->productivity;
            $previous_order = $productivity_detail->production_order;
            $previous_workstation = $productivity_detail->model_type::find($productivity_detail->model_id);
            $production_order = ProductionOrder::find($request->integer('production_order_id'));
            $service = app(ProductivityDetailService::class);

            $productivity_detail->model_type = $request->input('model_type');
            $productivity_detail->model_id = $request->integer('model_id');
            $productivity_detail->production_order_id = $production_order->id;
            $productivity_detail->rule_id = $service->unitTime($production_order, $productivity_detail->model, $shift->model)['rule']->id;
            $productivity_detail->start = $shift->date->format('Y-m-d').' '.$request->input('start');
            $settings = (array) $productivity_detail->settings;
            unset($settings['multiplier']);
            if (($service->multiplierOf($productivity_detail->model)['mode'] ?? null) === 'manual') {
                $settings['multiplier'] = (float) $request->input('multiplier');
            }
            // Tandas: las unidades las digita quien programa; el reparto las conserva
            if ($service->typedUnits($shift->model, $productivity_detail->model)) {
                $settings['scheduled'] = [...(array) ($settings['scheduled'] ?? []), 'units' => $request->integer('units')];
            }
            $productivity_detail->settings = $settings;
            $productivity_detail->save();

            $productivity_detail->models()->where('model_type', Employee::class)->delete();
            $borrowed = $service->workstations($shift->model)
                ->reject(fn ($item) => $item['model_type'] === $productivity_detail->model_type && $item['model_id'] === $productivity_detail->model_id)
                ->flatMap(fn ($item) => collect($item['employees'])->map(fn ($employee) => [$employee['id'], $item['name']]))
                ->mapWithKeys(fn ($pair) => [$pair[0] => $pair[1]]);

            foreach ($request->input('employees') as $employee_id) {
                $productivity_detail->models()->create([
                    'model_type' => Employee::class,
                    'model_id' => (int) $employee_id,
                    'settings' => isset($borrowed[$employee_id]) ? ['borrowed_from' => $borrowed[$employee_id]] : [],
                ]);
            }

            $service->reshare($shift, $production_order, $productivity_detail->model);

            if ($service->schedulesByLot($shift->model, $productivity_detail->model)) {
                $service->scheduleLot($productivity_detail, $request->input('end'));
            }

            if ($previous_order && $previous_order->id !== $production_order->id) {
                $service->reshare($shift, $previous_order, $previous_workstation);
            }

            return $this->successResponse(
                new ProductivityDetailScheduledResource($productivity_detail->refresh()->load(['model', 'employees' => ['person'], 'production_order' => ['technical_sheet' => ['product']], 'rule'])),
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
     *     path="/productivity/details/delete/{id}",
     *     tags={"Productivities › Details"},
     *     summary="Quitar un registro programado",
     *     description="Solo antes de que inicie el turno y si la estación no lo ha iniciado. Rehace el reparto del lote entre los registros que quedan; los contadores del lote no se tocan.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *
     *     @OA\Response(response=200, description="Quitado", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object"),
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
    public function delete(ProductivityDetailDeleteRequest $request, $id)
    {
        try {
            $productivity_detail = ProductivityDetail::with(['productivity', 'production_order', 'model'])->findOrFail($id);
            $productivity_detail->delete();

            app(ProductivityDetailService::class)->reshare($productivity_detail->productivity, $productivity_detail->production_order, $productivity_detail->model);

            return $this->successResponse(
                new ProductivityDetailScheduledResource($productivity_detail),
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
     *     path="/productivity/details/workstation/{token}/stop",
     *     tags={"Productivities › Details › Workstation"},
     *     summary="Registrar una parada",
     *     description="Registra que el trabajo en curso del puesto se detuvo ahora. La parada programada (almuerzo, pausa activa…) termina sola con la duración del catálogo; la no programada (paro mecánico, falta de insumos…) queda abierta hasta «Terminar parada». Una a la vez y solo mientras el turno no haya terminado. Responde 422 si la programada terminaría después del fin del turno.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"stop_id"},
     *
     *         @OA\Property(property="stop_id", type="integer", example=480, description="Parada del catálogo, programada o no programada")
     *     )),
     *
     *     @OA\Response(response=200, description="Trabajo en curso con la parada abierta", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity_detail", ref="#/components/schemas/ProductivityDetailInProgress")),
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
    public function stop_store(ProductivityDetailStopRequest $request, $token)
    {
        try {
            $service = app(ProductivityDetailService::class);
            $context = $service->workstationContext($token);
            $productivity_detail = $service->inProgress($context['workstation'], $request->integer('productivity_detail_id'));

            $stop = Stop::with('stop_type')->find($request->integer('stop_id'));

            // La parada detiene también a los acompañantes (el mismo grupo en PARCHE + RELOJERA)
            $service->companions($productivity_detail)
                ->reject(fn (ProductivityDetail $row) => ($open = $service->openStop($row)) && ! ($open->settings->automatic ?? false))
                ->prepend($productivity_detail)
                ->each(function (ProductivityDetail $row) use ($service, $stop) {
                    $service->endWaitingStop($row);
                    $service->startStop($row, $stop);
                });

            return $this->successResponse(
                new ProductivityDetailResource($productivity_detail->refresh()),
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
     *     path="/productivity/details/workstation/{token}/stop_end",
     *     tags={"Productivities › Details › Workstation"},
     *     summary="Terminar la parada no programada en curso",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(response=200, description="Trabajo en curso sin parada abierta", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity_detail", ref="#/components/schemas/ProductivityDetailInProgress")),
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
    public function stop_end(ProductivityDetailStopEndRequest $request, $token)
    {
        try {
            $service = app(ProductivityDetailService::class);
            $context = $service->workstationContext($token);
            $productivity_detail = $service->inProgress($context['workstation'], $request->integer('productivity_detail_id'));

            $service->companions($productivity_detail)
                ->filter(fn (ProductivityDetail $row) => ($open = $service->openStop($row))
                    && ! ($open->settings->automatic ?? false)
                    && ($open->settings->type ?? null) !== 'programmed')
                ->prepend($productivity_detail)
                ->each(fn (ProductivityDetail $row) => $service->endStop($row));
            $service->refreshWaitingStops($productivity_detail->productivity, $productivity_detail->production_order, $context['process']);

            return $this->successResponse(
                new ProductivityDetailResource($productivity_detail->refresh()),
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
     *     path="/productivity/details/workstation/{token}/plus",
     *     tags={"Productivities › Details › Workstation"},
     *     summary="Sumar una unidad (+1)",
     *     description="Proceso medido por unidades: suma una unidad en el contador del primer detalle recibido del lote que no esté lleno (Nacional, luego Medellín, luego Stara; dentro de cada destino por orden de llegada) y en el registro (settings.produced). El contador es una fila de movement_detail_quantities sin talla dentro del detalle recibido; su tope es lo recibido en ese detalle. 422 si el turno terminó, si hay una parada en curso o si el lote está completo.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(response=200, description="Trabajo en curso con los contadores actualizados", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity_detail", ref="#/components/schemas/ProductivityDetailInProgress")),
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
    public function plus(ProductivityDetailPlusRequest $request, $token)
    {
        try {
            $service = app(ProductivityDetailService::class);
            $context = $service->workstationContext($token);
            $productivity_detail = $service->inProgress($context['workstation'], $request->integer('productivity_detail_id'));

            // Un +1 suma en el registro y en sus acompañantes que aún pueden sumar (el mismo grupo en PARCHE + RELOJERA)
            $service->companions($productivity_detail)
                ->prepend($productivity_detail)
                ->reject(fn (ProductivityDetail $row) => $service->plusError($row, $context['process']))
                ->each(fn (ProductivityDetail $row) => $service->plus($row, $context['process']));
            $service->refreshWaitingStops($productivity_detail->productivity, $productivity_detail->production_order, $context['process']);

            return $this->successResponse(
                new ProductivityDetailResource($productivity_detail->refresh()),
                $this->getMessage('Success'),
                200
            );
        } catch (DomainException $e) {
            return $this->errorResponse(['message' => $e->getMessage()], 422);
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
     *     path="/productivity/details/workstation/{token}/minus",
     *     tags={"Productivities › Details › Workstation"},
     *     summary="Quitar una unidad (−1)",
     *     description="Quita la última unidad que sumó este mismo registro (por si se tocó de más); no toca lo de otros registros. 422 si el registro no tiene unidades.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(response=200, description="Trabajo en curso con los contadores actualizados", @OA\JsonContent(
     *
     *         @OA\Property(property="data", type="object", @OA\Property(property="productivity_detail", ref="#/components/schemas/ProductivityDetailInProgress")),
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
    public function minus(ProductivityDetailMinusRequest $request, $token)
    {
        try {
            $service = app(ProductivityDetailService::class);
            $context = $service->workstationContext($token);
            $productivity_detail = $service->inProgress($context['workstation'], $request->integer('productivity_detail_id'));

            // Con acompañantes se quita en los que recibieron el último +1: los que más llevan
            $rows = $service->companions($productivity_detail)->prepend($productivity_detail);
            $most = $rows->max(fn (ProductivityDetail $row) => $service->producedBy($row));
            $rows->filter(fn (ProductivityDetail $row) => $most > 0 && $service->producedBy($row) === $most)
                ->each(fn (ProductivityDetail $row) => $service->minus($row, $context['process']));
            $service->refreshWaitingStops($productivity_detail->productivity, $productivity_detail->production_order, $context['process']);

            return $this->successResponse(
                new ProductivityDetailResource($productivity_detail->refresh()),
                $this->getMessage('Success'),
                200
            );
        } catch (DomainException $e) {
            return $this->errorResponse(['message' => $e->getMessage()], 422);
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
     *     path="/productivity/details/workstation/{token}/finish",
     *     tags={"Productivities › Details › Workstation"},
     *     summary="Finalizar el trabajo en curso",
     *     description="Termina el registro: el fin oficial es ahora o, si ya pasó, la hora de fin del turno y devuelve tiempos y eficiencia (no se guardan: el frontend los calcula). Responde 422 si hay una parada en curso (programada o no programada): primero debe terminar. Solo el último puesto de la cadena del primer proceso (creates_ingress) envía `quantities` y crea un ingreso por destino: un detalle de movimiento cuyo origen es el registro de trabajo, en estado Pendiente, en el movimiento del proceso y destino (se crea si no existe), con las unidades por talla. El proceso lo aprueba en Movimientos y la curva de la orden se ajusta. Los demás puestos no envían unidades.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="token", in="path", required=true, description="uuid del puesto de trabajo (de su QR)", @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *
     *         @OA\Property(property="quantities", type="array", @OA\Items(
     *             @OA\Property(property="production_order_detail_id", type="integer", example=365, description="Destino de la orden"),
     *             @OA\Property(property="size_id", type="integer", example=374),
     *             @OA\Property(property="quantity", type="integer", example=20)
     *         ))
     *     )),
     *
     *     @OA\Response(response=200, description="Resumen del trabajo", @OA\JsonContent(
     *
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
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function finish(ProductivityDetailFinishRequest $request, $token)
    {
        try {
            $service = app(ProductivityDetailService::class);
            $context = $service->workstationContext($token);
            $productivity_detail = $service->inProgress($context['workstation'], $request->integer('productivity_detail_id'));
            // Los acompañantes (el mismo grupo en PARCHE + RELOJERA, todos con +1) terminan con él
            $companions = $service->companions($productivity_detail);

            foreach ($companions as $companion) {
                $service->finish($companion, $context['process'], null);
            }

            $finished = $service->finish(
                $productivity_detail,
                $context['process'],
                $request->input('quantities'),
                $service->totalAtFinish($context['process'], $context['workstation']) ? $request->integer('units') : null
            );
            $service->refreshWaitingStops($productivity_detail->productivity, $productivity_detail->production_order, $context['process']);

            return $this->successResponse(
                new ProductivityDetailFinishResource($finished),
                $this->getMessage('Success'),
                200
            );
        } catch (DomainException $e) {
            return $this->errorResponse(['message' => $e->getMessage()], 422);
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
     * Reprocesos de un trabajo iniciado y los pasos del lote a los que se pueden devolver unidades.
     */
    public function reprocess_all(ProductivityDetailReprocessAllRequest $request, $id)
    {
        try {
            $productivity_detail = ProductivityDetail::with(['productivity' => ['model'], 'production_order' => ['technical_sheet' => ['product']], 'model'])->findOrFail($id);

            return $this->successResponse(
                new ProductivityDetailReprocessResource($productivity_detail),
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
     * Registra un reproceso: las unidades vuelven a los pasos elegidos (dejan de estar hechas ahí y se vuelven a hacer).
     */
    public function reprocess_store(ProductivityDetailReprocessRequest $request, $id)
    {
        try {
            $productivity_detail = ProductivityDetail::with(['productivity' => ['model'], 'production_order' => ['technical_sheet' => ['product']], 'model'])->findOrFail($id);
            $settings = (array) $productivity_detail->settings;
            $settings['reprocesses'] = [...(array) ($settings['reprocesses'] ?? []), [
                'units' => $request->integer('units'),
                'steps' => array_values($request->input('steps')),
                'note' => $request->input('note'),
                'user_id' => $request->user()?->id,
                'at' => now()->format('Y-m-d H:i:s'),
            ]];
            $productivity_detail->settings = $settings;
            $productivity_detail->save();

            return $this->successResponse(
                new ProductivityDetailReprocessResource($productivity_detail->refresh()),
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
     * Anula un reproceso registrado por error: las unidades vuelven a contar como hechas en sus pasos.
     */
    public function reprocess_delete(ProductivityDetailReprocessDeleteRequest $request, $id, $index)
    {
        try {
            $productivity_detail = ProductivityDetail::with(['productivity' => ['model'], 'production_order' => ['technical_sheet' => ['product']], 'model'])->findOrFail($id);
            $settings = (array) $productivity_detail->settings;
            $reprocesses = (array) ($settings['reprocesses'] ?? []);
            unset($reprocesses[$request->integer('index')]);
            $settings['reprocesses'] = array_values($reprocesses);
            $productivity_detail->settings = $settings;
            $productivity_detail->save();

            return $this->successResponse(
                new ProductivityDetailReprocessResource($productivity_detail->refresh()),
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
