<?php

namespace App\Http\Controllers;

use App\Http\Requests\Movement\MovementAllRequest;
use App\Http\Requests\Movement\MovementApproveRequest;
use App\Http\Requests\Movement\MovementCancelRequest;
use App\Http\Requests\Movement\MovementDeclineRequest;
use App\Http\Requests\Movement\MovementFindRequest;
use App\Http\Requests\Movement\MovementSaveRequest;
use App\Http\Resources\Movement\MovementCollection;
use App\Http\Resources\Movement\MovementDetail\MovementDetailResource;
use App\Http\Resources\Movement\MovementResource;
use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\MovementDetailQuantity;
use App\Models\MovementDetailRedistribution;
use App\Models\Process;
use App\Models\ProductivityDetail;
use App\Services\MovementDetailService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @OA\Schema(
 *     schema="MovementDetailQuantity",
 *     type="object",
 *     description="Unidades de una talla en un traslado.",
 *
 *     @OA\Property(property="id", type="integer", example=9051),
 *     @OA\Property(property="movement_detail_id", type="integer", example=2210),
 *     @OA\Property(property="size_id", type="integer", example=101),
 *     @OA\Property(property="quantity", type="integer", example=15),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-05T14:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-05T14:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="MovementDetailRedistribution",
 *     type="object",
 *     description="Préstamo entre tallas dentro de un traslado: la talla de salida (`output_size_id`) le presta unidades a la talla de entrada (`input_size_id`) para completar lo pedido. Ejemplo: la talla 08 tiene 10 y la 10 tiene 20; para enviar 15 y 15, la talla 10 presta 5 a la 08.",
 *
 *     @OA\Property(property="id", type="integer", example=301),
 *     @OA\Property(property="movement_detail_id", type="integer", example=2210),
 *     @OA\Property(property="output_size_id", type="integer", description="Talla que presta.", example=102),
 *     @OA\Property(property="input_size_id", type="integer", description="Talla que recibe el préstamo.", example=101),
 *     @OA\Property(property="quantity", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-05T14:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-05T14:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="MovementTransfer",
 *     type="object",
 *     description="Traslado (`movement_details`): envío de unidades del movimiento de origen (`model_id`) al movimiento de destino (`movement_id`).",
 *
 *     @OA\Property(property="id", type="integer", example=2210),
 *     @OA\Property(property="movement_id", type="integer", description="Movimiento que recibe.", example=88),
 *     @OA\Property(property="model_type", type="string", example="App\Models\Movement"),
 *     @OA\Property(property="model_id", type="integer", description="Movimiento que envía.", example=87),
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "Aprobado", "Rechazado", "Cancelado"}, description="Aprobar y rechazar lo hace el proceso que recibe; cancelar, el que envía.", example="Pendiente"),
 *     @OA\Property(property="observation", type="string", nullable=true, example="SE ENVÍAN 15 Y 15; LA TALLA 08 COMPLETA CON 5 DE LA TALLA 10"),
 *     @OA\Property(property="settings", type="object", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-05T14:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-05T14:00:00.000000Z"),
 *     @OA\Property(property="movement_detail_quantities", type="array", @OA\Items(ref="#/components/schemas/MovementDetailQuantity")),
 *     @OA\Property(property="movement_detail_redistributions", type="array", @OA\Items(ref="#/components/schemas/MovementDetailRedistribution")),
 *     @OA\Property(property="user_created", nullable=true, description="Solo en `find`: usuario que creó el traslado, con empleado y persona.", type="object", additionalProperties=true),
 *     @OA\Property(property="user_updated", nullable=true, description="Solo en `find`: último usuario que lo modificó.", type="object", additionalProperties=true)
 * )
 *
 * @OA\Schema(
 *     schema="Movement",
 *     type="object",
 *     description="Movimiento: unidades de una curva de un lote (detalle de orden con destino) en un proceso.",
 *
 *     @OA\Property(property="id", type="integer", example=88),
 *     @OA\Property(property="model_type", type="string", description="Solo en `find`.", example="App\Models\Process"),
 *     @OA\Property(property="model_id", type="integer", description="Proceso. Solo en `find`.", example=25),
 *     @OA\Property(property="production_order_detail_id", type="integer", description="Solo en `find`.", example=40122),
 *     @OA\Property(property="status", type="string", enum={"Ingreso", "Movimiento", "Salida"}, description="Ingreso = recibió y aún no envía; Movimiento = ya envió una parte o lo enviado espera que el otro proceso lo acepte; Salida = envió todo lo recibido y se lo aceptaron (los rechazados y cancelados no cuentan). Se recalcula al crear o editar un traslado y al aprobarlo, rechazarlo o cancelarlo.", example="Movimiento"),
 *     @OA\Property(property="created_at", type="string", example="2026-11-05 09:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-11-05 09:00:00"),
 *     @OA\Property(property="deleted_at", nullable=true, description="Solo en `all`; la tabla no tiene esa columna (siempre `null`).", example=null),
 *     @OA\Property(property="model", description="Proceso. En `find` incluye `before_processes` y `after_processes`.", allOf={@OA\Schema(ref="#/components/schemas/Subitem")}),
 *     @OA\Property(
 *         property="production_order_detail",
 *         type="object",
 *         additionalProperties=true,
 *         description="Curva del lote (detalle de orden con destino): `destination`, `production_order` (con ficha y colección), cantidades por talla (`production_order_detail_quantities`, saldo actual) y el producto (`model`, con marca y tallas en `find`).",
 *         @OA\Property(property="id", type="integer", example=40122),
 *         @OA\Property(property="production_order_id", type="integer", example=1312),
 *         @OA\Property(property="destination", type="string", enum={"NACIONAL", "MEDELLIN", "STARA"}, example="NACIONAL")
 *     ),
 *     @OA\Property(property="movement_details_input", type="array", description="Traslados que llegan a este movimiento.", @OA\Items(ref="#/components/schemas/MovementTransfer")),
 *     @OA\Property(property="movement_details_output", type="array", description="Traslados que salen de este movimiento.", @OA\Items(ref="#/components/schemas/MovementTransfer"))
 * )
 *
 * @OA\Schema(
 *     schema="MovementListResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="movements", type="array", @OA\Items(ref="#/components/schemas/Movement")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="MovementResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="movement", ref="#/components/schemas/Movement"),
 *         @OA\Property(property="statuses", type="array", description="Estados posibles de un traslado.", @OA\Items(type="string"), example={"Pendiente", "Aprobado", "Rechazado", "Cancelado"})
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="MovementTransferResponse",
 *     type="object",
 *     description="Respuesta de save, approve, decline y cancel: el traslado con sus cantidades y redistribuciones.",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="movement_detail", ref="#/components/schemas/MovementTransfer"),
 *         @OA\Property(property="statuses", type="array", @OA\Items(type="string"), example={"Pendiente", "Aprobado", "Rechazado", "Cancelado"})
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="MovementTransferRequest",
 *     type="object",
 *     description="Cuerpo común de save, approve, decline y cancel (los cuatro Requests son copias; ver pendientes).",
 *     required={"movement_origin_id", "origin", "destination", "quantities"},
 *
 *     @OA\Property(property="movement_origin_id", type="integer", description="Movimiento desde el que se actúa (el que envía en save; el que recibe en approve/decline). Obligatorio; si se envía `movement_detail_id`, el traslado debe pertenecer a este movimiento.", example=87),
 *     @OA\Property(property="movement_detail_id", type="integer", description="Traslado existente. En save, si se envía, se edita ese traslado en vez de crear uno nuevo. En approve/decline/cancel es el traslado sobre el que se actúa; debe estar Pendiente.", example=2210),
 *     @OA\Property(
 *         property="origin",
 *         type="object",
 *         required={"production_order_detail_id", "process"},
 *         @OA\Property(property="production_order_detail_id", type="integer", description="Curva del lote que envía.", example=40122),
 *         @OA\Property(property="process", type="object", @OA\Property(property="model_type", type="string", example="App\Models\Process"), @OA\Property(property="model_id", type="integer", example=24))
 *     ),
 *     @OA\Property(
 *         property="destination",
 *         type="object",
 *         required={"production_order_detail_id", "process"},
 *         description="Puede ser la misma curva o la de otro lote. El proceso debe ser el mismo, el anterior o el siguiente del de origen.",
 *         @OA\Property(property="production_order_detail_id", type="integer", example=40122),
 *         @OA\Property(property="process", type="object", @OA\Property(property="model_type", type="string", example="App\Models\Process"), @OA\Property(property="model_id", type="integer", example=25))
 *     ),
 *     @OA\Property(
 *         property="quantities",
 *         type="array",
 *         minItems=1,
 *         description="Unidades por talla (sin tallas repetidas). En approve, lo realmente recibido (no puede superar lo enviado; si es menor, se reducen las redistribuciones de esa talla).",
 *
 *         @OA\Items(type="object", required={"size_id", "quantity"}, @OA\Property(property="id", type="integer", nullable=true, description="Cantidad existente (al editar o aprobar).", example=null), @OA\Property(property="size_id", type="integer", example=101), @OA\Property(property="quantity", type="integer", example=15))
 *     ),
 *
 *     @OA\Property(
 *         property="redistributions",
 *         type="array",
 *         nullable=true,
 *         description="Préstamos entre tallas. La talla que presta debe tener unidades suficientes y ser distinta de la que recibe.",
 *
 *         @OA\Items(type="object", required={"output_size_id", "input_size_id", "quantity"}, @OA\Property(property="output_size_id", type="integer", example=102), @OA\Property(property="input_size_id", type="integer", example=101), @OA\Property(property="quantity", type="integer", minimum=1, example=5))
 *     ),
 *
 *     @OA\Property(property="status", type="string", enum={"Pendiente", "Aprobado", "Rechazado", "Cancelado"}, description="Opcional; no lo usa ningún método."),
 *     @OA\Property(property="observation", type="string", maxLength=255, nullable=true, example="SE ENVÍAN 15 Y 15; LA TALLA 08 COMPLETA CON 5 DE LA TALLA 10")
 * )
 */
class MovementController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/movements/all",
     *     tags={"Movements"},
     *     summary="Listar los movimientos",
     *     description="Cada movimiento trae su proceso, la curva del lote (con orden, ficha, colección y producto) y sus traslados de entrada y salida con cantidades. Sin `per_page` devuelve todo (respuesta pesada).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar, junto con `dir`. Son las columnas de la tabla del frontend; las que viven en otra tabla (marca, colección, categoría, proceso…) se resuelven con las relaciones de `Movement::sortable_columns()`. Las columnas calculadas (lote, cantidad, inventario, pendiente) no se pueden ordenar.",
     *
     *         @OA\Schema(type="string", enum={"id", "created_at", "updated_at", "status", "trademark", "collection", "garment_type", "reference", "color", "category", "destination", "process"}, example="created_at")
     *     ),
     *
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(
     *         name="with_trashed",
     *         in="query",
     *         required=false,
     *         description="Se valida pero no tiene efecto: los movimientos no tienen borrado lógico.",
     *
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de movimientos cargada correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MovementListResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(MovementAllRequest $request)
    {
        try {
            $movements = Movement::with(['movement_details_input' => ['movement_detail_quantities'], 'movement_details_output' => ['movement_detail_quantities'], 'model', 'production_order_detail' => ['production_order_detail_quantities', 'production_order' => ['technical_sheet' => ['collection']], 'model' => ['trademark', 'subcategory' => ['category'], 'technical_sheets' => ['collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type'], 'technical_sheet' => ['collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type']]]])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $movements = $movements->paginate($request->integer('per_page', $movements->count()));

            return $this->successResponse(
                new MovementCollection($movements),
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
     *     path="/movements/find/{id}",
     *     tags={"Movements"},
     *     summary="Obtener un movimiento con sus traslados",
     *     description="Para la pantalla de resumen y traslado de unidades: traslados de entrada y salida (con cantidades, redistribuciones y quién los creó o modificó), el proceso con sus procesos anterior y siguiente, y la curva del lote con las tallas de la marca.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Movimiento cargado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MovementResponse")
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(MovementFindRequest $request, $id)
    {
        try {
            $movement = Movement::with(['movement_details_output' => ['movement' => ['model', 'production_order_detail' => ['model', 'production_order']], 'model' => fn (MorphTo $morph) => $morph->morphWith([Movement::class => ['model', 'production_order_detail' => ['model' => ['trademark'], 'production_order' => ['technical_sheet' => ['collection']]]], ProductivityDetail::class => ['model', 'productivity' => ['model']]]), 'movement_detail_redistributions', 'movement_detail_quantities', 'user_created' => ['employee' => ['person']], 'user_updated' => ['employee' => ['person']]],  'movement_details_input' => ['movement' => ['model', 'production_order_detail' => ['model', 'production_order']], 'model' => fn (MorphTo $morph) => $morph->morphWith([Movement::class => ['model', 'production_order_detail' => ['model' => ['trademark'], 'production_order' => ['technical_sheet' => ['collection']]]], ProductivityDetail::class => ['model', 'productivity' => ['model']]]), 'movement_detail_redistributions', 'movement_detail_quantities', 'user_created' => ['employee' => ['person']], 'user_updated' => ['employee' => ['person']]], 'model' => ['after_processes', 'before_processes'], 'production_order_detail' => ['production_order_detail_quantities', 'production_order' => ['technical_sheet' => ['collection']], 'model' => ['trademark' => ['sizes'], 'subcategory' => ['category'], 'technical_sheet' => ['collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type']]]])
                ->findOrFail($id);

            return $this->successResponse(
                new MovementResource($movement),
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
     *     path="/movements/save",
     *     tags={"Movements"},
     *     summary="Trasladar unidades a otro proceso (o editar un traslado)",
     *     description="Sin `movement_detail_id`: crea un traslado **Pendiente** desde el movimiento de origen (curva + proceso) hacia el de destino, que se crea en estado Ingreso si no existe. El destino puede ser la misma curva u otro lote. Con `movement_detail_id`: edita la observación y las cantidades de ese traslado (si una talla baja, se reducen sus redistribuciones). El movimiento de origen debe existir: hoy el primero de cada lote no lo crea este código (vendrá de la medición de productividad).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/MovementTransferRequest",
     *             example={
     *                 "movement_origin_id": 87,
     *                 "origin": {"production_order_detail_id": 40122, "process": {"model_type": "App\Models\Process", "model_id": 24}},
     *                 "destination": {"production_order_detail_id": 40122, "process": {"model_type": "App\Models\Process", "model_id": 25}},
     *                 "quantities": {{"id": null, "size_id": 101, "quantity": 15}, {"id": null, "size_id": 102, "quantity": 15}},
     *                 "redistributions": {{"output_size_id": 102, "input_size_id": 101, "quantity": 5}},
     *                 "observation": "SE ENVÍAN 15 Y 15; LA TALLA 08 COMPLETA CON 5 DE LA TALLA 10"
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Traslado guardado correctamente (también al crearlo: responde 200, no 201).",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MovementTransferResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos: proceso destino no relacionado, traslado que no pertenece al movimiento o que ya no está Pendiente, cantidad recibida mayor a la enviada, redistribución entre la misma talla o sin unidades suficientes en la talla que presta, etc.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"movement_origin_id": "Identificador del movimiento de origen", "destination.process.model_id": "Proceso de destino", "quantities": "Cantidades del traslado"},
     *                 "errors": {"destination.process.model_id": {"El proceso destino debe ser el mismo proceso o un proceso anterior o siguiente del proceso origen."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function save(MovementSaveRequest $request)
    {
        try {
            if ($request->filled('movement_detail_id')) {
                $movement_detail = MovementDetail::findOrFail($request->input('movement_detail_id'));

                $movement_detail->update([
                    'observation' => $request->input('observation'),
                ]);

                $requested_quantities = collect(
                    $request->input('quantities', [])
                );

                foreach ($movement_detail->movement_detail_quantities as $quantity) {
                    $requested_quantity = $requested_quantities
                        ->firstWhere('id', $quantity->id);

                    if (! $requested_quantity) {
                        continue;
                    }

                    $difference = max(0, (int) $quantity->quantity - (int) $requested_quantity['quantity']);

                    if ($difference === 0) {
                        continue;
                    }

                    $redistributions = MovementDetailRedistribution::where('movement_detail_id', $movement_detail->id)
                        ->where('input_size_id', $quantity->size_id)
                        ->orderBy('id')
                        ->get();

                    foreach ($redistributions as $redistribution) {
                        if ($difference <= 0) {
                            break;
                        }

                        $aux = max(0, (int) $redistribution->quantity - $difference);

                        $returned = (int) $redistribution->quantity - $aux;

                        $redistribution->update([
                            'quantity' => $aux,
                        ]);

                        $difference -= $returned;
                    }
                }

                foreach ($request->input('quantities', []) as $quantity) {
                    MovementDetailQuantity::updateOrCreate(
                        [
                            'id' => $quantity['id'] ?? null,
                            'movement_detail_id' => $movement_detail->id,
                            'size_id' => $quantity['size_id'],
                        ],
                        [
                            'quantity' => $quantity['quantity'],
                        ]
                    );
                }

            } else {
                $origin_movement = Movement::where('production_order_detail_id', $request->input('origin.production_order_detail_id'))
                    ->whereHasMorph(
                        'model',
                        [Process::class],
                        function (Builder $query) use ($request) {
                            $query->where(
                                'id',
                                $request->input('origin.process.model_id')
                            );
                        }
                    )
                    ->firstOrFail();

                $destination_movement = Movement::firstOrCreate(
                    [
                        'production_order_detail_id' => $request->input(
                            'destination.production_order_detail_id'
                        ),
                        'model_type' => $request->input(
                            'destination.process.model_type'
                        ),
                        'model_id' => $request->input(
                            'destination.process.model_id'
                        ),
                    ],
                    [
                        'status' => 'Ingreso',
                    ]
                );

                $movement_detail = MovementDetail::create([
                    'movement_id' => $destination_movement->id,
                    'model_type' => Movement::class,
                    'model_id' => $origin_movement->id,
                    'status' => 'Pendiente',
                    'observation' => $request->input('observation'),
                ]);

                foreach ($request->input('quantities', []) as $quantity) {
                    MovementDetailQuantity::create([
                        'movement_detail_id' => $movement_detail->id,
                        'size_id' => $quantity['size_id'],
                        'quantity' => $quantity['quantity'],
                    ]);
                }

                foreach ($request->input('redistributions', []) as $redistribution) {
                    MovementDetailRedistribution::create([
                        'movement_detail_id' => $movement_detail->id,
                        'output_size_id' => $redistribution['output_size_id'],
                        'input_size_id' => $redistribution['input_size_id'],
                        'quantity' => $redistribution['quantity'],
                    ]);
                }
            }

            // Con las cantidades ya guardadas, el movimiento que envía pasa a Movimiento o Salida
            app(MovementDetailService::class)->refreshOriginStatus($movement_detail);

            $movement_detail->load([
                'movement' => ['model'],
                'model' => fn (MorphTo $morph) => $morph->morphWith([
                    Movement::class => [
                        'model',
                        'production_order_detail' => [
                            'model',
                            'production_order' => [
                                'technical_sheet' => ['collection'],
                            ],
                        ],
                    ],
                    ProductivityDetail::class => ['model', 'productivity' => ['model']],
                ]),
                'movement_detail_quantities',
                'movement_detail_redistributions',
                'user_created' => ['employee' => ['person']],
            ]);

            return $this->successResponse(
                new MovementDetailResource($movement_detail),
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
     *     path="/movements/approve",
     *     tags={"Movements"},
     *     summary="Aprobar un traslado (proceso que recibe)",
     *     description="Marca el traslado como Aprobado y guarda las cantidades realmente recibidas. Si se recibe menos de lo enviado en una talla, se reducen las redistribuciones de esa talla. El traslado debe estar Pendiente. Al cambiar el estado se recalculan el estado del movimiento de origen y el saldo por talla del lote.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/MovementTransferRequest",
     *             example={
     *                 "movement_origin_id": 88, "movement_detail_id": 2210,
     *                 "origin": {"production_order_detail_id": 40122, "process": {"model_type": "App\Models\Process", "model_id": 24}},
     *                 "destination": {"production_order_detail_id": 40122, "process": {"model_type": "App\Models\Process", "model_id": 25}},
     *                 "quantities": {{"id": 9051, "size_id": 101, "quantity": 15}, {"id": 9052, "size_id": 102, "quantity": 14}},
     *                 "observation": "LLEGÓ UNA UNIDAD MENOS DE LA TALLA 10"
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Traslado aprobado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MovementTransferResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos: proceso destino no relacionado, traslado que no pertenece al movimiento o que ya no está Pendiente, cantidad recibida mayor a la enviada, redistribución entre la misma talla o sin unidades suficientes en la talla que presta, etc.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"movement_origin_id": "Identificador del movimiento de origen", "destination.process.model_id": "Proceso de destino", "quantities": "Cantidades del traslado"},
     *                 "errors": {"destination.process.model_id": {"El proceso destino debe ser el mismo proceso o un proceso anterior o siguiente del proceso origen."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function approve(MovementApproveRequest $request)
    {
        try {
            $movement_detail = MovementDetail::findOrFail($request->input('movement_detail_id'));

            $requested_quantities = collect($request->input('quantities', []));

            foreach ($movement_detail->movement_detail_quantities as $movement_detail_quantity) {
                $requested_quantity = $requested_quantities
                    ->firstWhere('id', $movement_detail_quantity->id);

                if (! $requested_quantity) {
                    continue;
                }

                $difference = max(0, (int) $movement_detail_quantity->quantity - (int) $requested_quantity['quantity']);

                if ($difference === 0) {
                    continue;
                }

                $redistributions = MovementDetailRedistribution::where('movement_detail_id', $movement_detail->id)
                    ->where('input_size_id', $movement_detail_quantity->size_id)
                    ->orderBy('id')
                    ->get();

                foreach ($redistributions as $redistribution) {
                    if ($difference <= 0) {
                        break;
                    }

                    $aux = max(0, (int) $redistribution->quantity - $difference);

                    $returned = (int) $redistribution->quantity - $aux;

                    $redistribution->update([
                        'quantity' => $aux,
                    ]);

                    $difference -= $returned;
                }
            }

            foreach ($request->input('quantities', []) as $quantity) {
                MovementDetailQuantity::updateOrCreate(
                    [
                        'id' => $quantity['id'] ?? null,
                        'movement_detail_id' => $movement_detail->id,
                        'size_id' => $quantity['size_id'],
                    ],
                    [
                        'quantity' => $quantity['quantity'],
                    ]
                );
            }

            $movement_detail->update([
                'status' => 'Aprobado',
                'observation' => $request->input('observation'),
            ]);

            $movement_detail->load([
                'movement' => ['model'],
                'model' => fn (MorphTo $morph) => $morph->morphWith([
                    Movement::class => [
                        'model',
                        'production_order_detail' => [
                            'production_order_detail_quantities',
                            'model',
                            'production_order' => [
                                'technical_sheet' => ['collection'],
                            ],
                        ],
                        'movement_details_input' => ['movement_detail_quantities'],
                        'movement_details_output' => ['movement_detail_quantities'],
                    ],
                    ProductivityDetail::class => ['model', 'productivity' => ['model']],
                ]),
                'movement_detail_quantities',
                'movement_detail_redistributions',
                'user_created' => ['employee' => ['person']],
            ]);

            return $this->successResponse(
                new MovementDetailResource($movement_detail),
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
     *     path="/movements/decline",
     *     tags={"Movements"},
     *     summary="Rechazar un traslado (proceso que recibe)",
     *     description="Marca el traslado como Rechazado con una observación. Solo usa `movement_detail_id` y `observation`, pero hoy el Request exige el cuerpo completo del traslado (ver pendientes). El traslado debe estar Pendiente.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/MovementTransferRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Traslado rechazado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MovementTransferResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos: proceso destino no relacionado, traslado que no pertenece al movimiento o que ya no está Pendiente, cantidad recibida mayor a la enviada, redistribución entre la misma talla o sin unidades suficientes en la talla que presta, etc.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"movement_origin_id": "Identificador del movimiento de origen", "destination.process.model_id": "Proceso de destino", "quantities": "Cantidades del traslado"},
     *                 "errors": {"destination.process.model_id": {"El proceso destino debe ser el mismo proceso o un proceso anterior o siguiente del proceso origen."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function decline(MovementDeclineRequest $request)
    {
        try {
            $movement_detail = MovementDetail::findOrFail(
                $request->input('movement_detail_id')
            );

            $movement_detail->update([
                'status' => 'Rechazado',
                'observation' => $request->input('observation'),
            ]);

            $movement_detail->load([
                'movement' => ['model'],
                'model' => fn (MorphTo $morph) => $morph->morphWith([
                    Movement::class => [
                        'model',
                        'production_order_detail' => [
                            'model',
                            'production_order' => [
                                'technical_sheet' => ['collection'],
                            ],
                        ],
                    ],
                    ProductivityDetail::class => ['model', 'productivity' => ['model']],
                ]),
                'movement_detail_quantities',
                'movement_detail_redistributions',
                'user_created' => ['employee' => ['person']],
            ]);

            return $this->successResponse(
                new MovementDetailResource($movement_detail),
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
     *     path="/movements/cancel",
     *     tags={"Movements"},
     *     summary="Cancelar un traslado (proceso que envía)",
     *     description="Marca el traslado como Cancelado con una observación, antes de que el destino lo reciba. Solo usa `movement_detail_id` y `observation`, pero hoy el Request exige el cuerpo completo del traslado (ver pendientes). El traslado debe estar Pendiente.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/MovementTransferRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Traslado cancelado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/MovementTransferResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos: proceso destino no relacionado, traslado que no pertenece al movimiento o que ya no está Pendiente, cantidad recibida mayor a la enviada, redistribución entre la misma talla o sin unidades suficientes en la talla que presta, etc.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"movement_origin_id": "Identificador del movimiento de origen", "destination.process.model_id": "Proceso de destino", "quantities": "Cantidades del traslado"},
     *                 "errors": {"destination.process.model_id": {"El proceso destino debe ser el mismo proceso o un proceso anterior o siguiente del proceso origen."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function cancel(MovementCancelRequest $request)
    {
        try {
            $movement_detail = MovementDetail::findOrFail(
                $request->input('movement_detail_id')
            );

            $movement_detail->update([
                'status' => 'Cancelado',
                'observation' => $request->input('observation'),
            ]);

            $movement_detail->load([
                'movement' => ['model'],
                'model' => fn (MorphTo $morph) => $morph->morphWith([
                    Movement::class => [
                        'model',
                        'production_order_detail' => [
                            'model',
                            'production_order' => [
                                'technical_sheet' => ['collection'],
                            ],
                        ],
                    ],
                    ProductivityDetail::class => ['model', 'productivity' => ['model']],
                ]),
                'movement_detail_quantities',
                'movement_detail_redistributions',
                'user_created' => ['employee' => ['person']],
            ]);

            return $this->successResponse(
                new MovementDetailResource($movement_detail),
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
