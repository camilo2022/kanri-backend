<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\MovementDetailQuantity;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\Productivity;
use App\Models\Rule;
use App\Models\Stop;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Registro de trabajo en un puesto (escaneo del QR del puesto → referencia → iniciar → paradas → finalizar).
 *
 * - Mientras se trabaja, el trabajo en curso vive en el turno: productivity.settings.work_in_progress.<puesto>.
 * - Al finalizar se guarda como movimientos: un detalle por destino de la orden (Nacional, Medellín, Stara) cuyo
 *   origen (model) es el turno, en estado Pendiente, con las unidades por talla, los tiempos y la eficiencia en
 *   settings y la regla enlazada por model_has_subitems. El proceso lo aprueba en Movimientos y la curva se ajusta.
 *
 * Tiempos (todo en minutos):
 * - El primer trabajo del puesto en el turno empieza a la hora de inicio del turno; los siguientes, donde terminó
 *   el anterior (sin tiempos vacíos).
 * - Paradas: las programadas del turno (settings.stops) que caen dentro del trabajo y las no programadas que se
 *   registraron en él. Si se cruzan, el rato común se descuenta una sola vez.
 * - Por lote (todas las personas de la regla paran):
 *   disponible = duración × personas; muerto = paradas × personas; trabajado = disponible − muerto;
 *   estándar = tiempo de la regla × personas; eficiencia = estándar ÷ trabajado × 100.
 */
class WorkLogService
{
    /**
     * Clave del puesto dentro del turno: "process_205", "subprocess_12", "operation_215".
     */
    public static function key(Model $workstation): string
    {
        return strtolower(class_basename($workstation)).'_'.$workstation->id;
    }

    /**
     * Trabajo en curso del puesto en el turno, o null.
     */
    public function inProgress(Productivity $shift, Model $workstation): ?array
    {
        $work = data_get($this->settings($shift), 'work_in_progress.'.self::key($workstation));

        return is_array($work) ? $work : null;
    }

    /**
     * Hora de inicio del siguiente trabajo del puesto: el fin del último que terminó en el turno o, si es el
     * primero, la hora de inicio del turno.
     */
    public function startTime(Productivity $shift, Model $workstation): Carbon
    {
        $last = $shift->movement_details()
            ->where('settings->workstation', self::key($workstation))
            ->get()
            ->map(fn ($detail) => $detail->settings->end ?? null)
            ->filter()
            ->max();

        return $last ? Carbon::parse($last) : $this->at($shift, $shift->start_time);
    }

    /**
     * Paradas programadas del turno con su fecha y hora completas.
     */
    public function shiftStops(Productivity $shift): array
    {
        $names = Stop::whereIn('id', collect($this->settings($shift)['stops'] ?? [])->pluck('stop_id'))->pluck('name', 'id');

        return collect($this->settings($shift)['stops'] ?? [])
            ->map(fn ($stop) => [
                'stop_id' => $stop['stop_id'],
                'name' => $names[$stop['stop_id']] ?? null,
                'type' => 'programmed',
                'start' => $this->at($shift, $stop['start_time'])->format('Y-m-d H:i:s'),
                'end' => $this->at($shift, $stop['end_time'])->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->all();
    }

    /**
     * Parada programada del turno en curso en ese momento, o null.
     */
    public function programmedStopAt(Productivity $shift, Carbon $moment): ?array
    {
        return collect($this->shiftStops($shift))
            ->first(fn ($stop) => $moment->gte(Carbon::parse($stop['start'])) && $moment->lt(Carbon::parse($stop['end'])));
    }

    /**
     * Tiempos y eficiencia de un trabajo que termina en $end.
     */
    public function summary(Productivity $shift, array $work, Carbon $end, Rule $rule): array
    {
        $start = Carbon::parse($work['start']);

        // Paradas que tocan el trabajo, recortadas a su rango
        $stops = collect($this->shiftStops($shift))
            ->merge(collect($work['stops'] ?? [])->map(fn ($stop) => array_merge($stop, ['type' => 'unprogrammed', 'end' => $stop['end'] ?? $end->format('Y-m-d H:i:s')])))
            ->map(function ($stop) use ($start, $end) {
                $from = Carbon::parse($stop['start'])->max($start);
                $to = Carbon::parse($stop['end'])->min($end);

                return $to->gt($from) ? array_merge($stop, [
                    'start' => $from->format('Y-m-d H:i:s'),
                    'end' => $to->format('Y-m-d H:i:s'),
                    'minutes' => $this->minutes($from, $to),
                ]) : null;
            })
            ->filter()
            ->sortBy('start')
            ->values();

        // Tiempo muerto: la unión de las paradas, para no descontar dos veces el rato en que se cruzan
        $dead = 0;
        $cursor = null;

        foreach ($stops as $stop) {
            $from = Carbon::parse($stop['start']);
            $to = Carbon::parse($stop['end']);

            if ($cursor && $from->lt($cursor)) {
                $from = $cursor;
            }

            if ($to->gt($from)) {
                $dead += $this->minutes($from, $to);
                $cursor = $to;
            }
        }

        $people = max(1, (int) ($rule->settings->people ?? 1));
        $time = (float) ($rule->settings->time ?? 0);
        $standard = ($rule->settings->time_unit ?? 'min') === 'seg' ? $time / 60 : $time;

        $duration = $this->minutes($start, $end);
        $available = $duration * $people;
        $deadPeople = $dead * $people;
        $worked = $available - $deadPeople;
        $standardPeople = $standard * $people;

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'people' => $people,
            'duration_minutes' => round($duration, 2),
            'stop_minutes' => round($dead, 2),
            'available_minutes' => round($available, 2),
            'dead_minutes' => round($deadPeople, 2),
            'worked_minutes' => round($worked, 2),
            'standard_minutes' => round($standardPeople, 2),
            'efficiency' => $worked > 0 ? round($standardPeople / $worked * 100, 2) : null,
            'stops' => $stops->all(),
        ];
    }

    /**
     * Destinos de la orden con su curva actual (Nacional, Medellín, Stara, en ese orden; sin los que no tienen
     * unidades).
     */
    public function destinations(ProductionOrder $productionOrder): array
    {
        $order = array_flip(ProductionOrder::destinations());

        return $productionOrder->production_order_details_product()
            ->with(['model', 'production_order_detail_quantities' => fn ($query) => $query->with('size')->orderBy('id')])
            ->get()
            ->filter(fn ($detail) => $detail->production_order_detail_quantities->sum('quantity') > 0)
            ->sortBy(fn ($detail) => $order[$detail->destination] ?? 99)
            ->map(fn ($detail) => [
                'production_order_detail_id' => $detail->id,
                'destination' => $detail->destination,
                // Cada destino puede tener su propia referencia (producto)
                'reference' => $detail->model?->code,
                'sizes' => $detail->production_order_detail_quantities->map(fn ($quantity) => [
                    'size_id' => $quantity->size_id,
                    'name' => $quantity->size?->name,
                    'quantity' => (int) $quantity->quantity,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Lo que se trabajó en un turno: los trabajos finalizados (detalles de movimiento cuyo origen es el turno,
     * agrupados por puesto e inicio, porque un trabajo crea un detalle por destino) y los que están en curso.
     */
    public function shiftWorkLogs(Productivity $shift): array
    {
        $finished = $shift->movement_details()
            ->with([
                'movement.production_order_detail' => ['model', 'production_order.technical_sheet.garment_type'],
                'movement_detail_quantities.size',
                'rules',
            ])
            ->get()
            ->groupBy(fn ($detail) => ($detail->settings->workstation ?? '').'|'.($detail->settings->start ?? ''))
            ->map(function ($details) {
                $first = $details->first();
                $settings = $first->settings;
                $productionOrder = $first->movement?->production_order_detail?->production_order;
                $statuses = $details->pluck('status')->unique()->values();

                return [
                    'status' => $statuses->contains(MovementDetail::PENDING) ? MovementDetail::PENDING : $statuses->first(),
                    'workstation' => $this->workstationName($settings->workstation ?? null),
                    'production_order' => ['id' => $productionOrder?->id, 'code' => $productionOrder?->consecutive, 'lot' => $productionOrder?->cut],
                    'reference' => $first->movement?->production_order_detail?->model?->code,
                    'garment_type' => $productionOrder?->technical_sheet?->garment_type?->name,
                    'rule' => $first->rules->first() ? ['id' => $first->rules->first()->id, 'name' => $first->rules->first()->name] : null,
                    'start' => $settings->start ?? null,
                    'end' => $settings->end ?? null,
                    'people' => $settings->people ?? null,
                    'duration_minutes' => $settings->duration_minutes ?? null,
                    'stop_minutes' => $settings->stop_minutes ?? null,
                    'worked_minutes' => $settings->worked_minutes ?? null,
                    'standard_minutes' => $settings->standard_minutes ?? null,
                    'efficiency' => $settings->efficiency ?? null,
                    'stops' => $settings->stops ?? [],
                    'units' => (int) $details->sum(fn ($detail) => $detail->movement_detail_quantities->sum('quantity')),
                    'destinations' => $details->map(fn ($detail) => [
                        'production_order_detail_id' => $detail->movement?->production_order_detail_id,
                        'destination' => $detail->movement?->production_order_detail?->destination,
                        'reference' => $detail->movement?->production_order_detail?->model?->code,
                        'status' => $detail->status,
                        'sizes' => $detail->movement_detail_quantities->map(fn ($quantity) => [
                            'size_id' => $quantity->size_id,
                            'name' => $quantity->size?->name,
                            'quantity' => (int) $quantity->quantity,
                        ])->values()->all(),
                    ])->values()->all(),
                ];
            });

        // Trabajos en curso: aún no tienen unidades ni eficiencia
        $inProgress = collect($this->settings($shift)['work_in_progress'] ?? [])
            ->map(function ($work, $key) {
                $productionOrder = ProductionOrder::with(['technical_sheet.product', 'technical_sheet.garment_type'])->find($work['production_order_id'] ?? null);

                return [
                    'status' => 'En curso',
                    'workstation' => $this->workstationName($key),
                    'production_order' => ['id' => $productionOrder?->id, 'code' => $productionOrder?->consecutive, 'lot' => $productionOrder?->cut],
                    'reference' => $productionOrder?->technical_sheet?->product?->code,
                    'garment_type' => $productionOrder?->technical_sheet?->garment_type?->name,
                    'rule' => ($rule = Rule::find($work['rule_id'] ?? null)) ? ['id' => $rule->id, 'name' => $rule->name] : null,
                    'start' => $work['start'] ?? null,
                    'end' => null,
                    'stops' => $work['stops'] ?? [],
                    'units' => null,
                    'efficiency' => null,
                    'destinations' => [],
                ];
            });

        return $finished->values()->merge($inProgress->values())->sortBy('start')->values()->all();
    }

    /**
     * Nombre del puesto a partir de su clave ("process_205" → "CORTE").
     */
    protected function workstationName(?string $key): ?string
    {
        [$type, $id] = array_pad(explode('_', (string) $key, 2), 2, null);

        $model = match ($type) {
            'process' => Process::class,
            'subprocess' => \App\Models\Subprocess::class,
            'operation' => \App\Models\Operation::class,
            default => null,
        };

        return $model ? $model::withTrashed()->find($id)?->name : null;
    }

    /**
     * ¿La orden ya tiene un ingreso por productividad en el proceso (pendiente o aprobado)?
     */
    public function alreadyRegistered(ProductionOrder $productionOrder, Process $process): bool
    {
        return MovementDetail::where('model_type', Productivity::class)
            ->whereIn('status', [MovementDetail::PENDING, MovementDetail::APPROVED])
            ->whereHas('movement', function ($query) use ($productionOrder, $process) {
                $query->where('model_type', Process::class)
                    ->where('model_id', $process->id)
                    ->whereIn('production_order_detail_id', $productionOrder->production_order_details_product()->select('id'));
            })
            ->exists();
    }

    /**
     * ¿Otro puesto del proceso está trabajando ahora mismo esta orden?
     */
    public function inProgressElsewhere(ProductionOrder $productionOrder, Process $process, Model $workstation): bool
    {
        return Productivity::where('model_type', Process::class)
            ->where('model_id', $process->id)
            ->whereDate('date', '>=', today()->subDay())
            ->get()
            ->contains(fn ($shift) => collect($this->settings($shift)['work_in_progress'] ?? [])
                ->except(self::key($workstation))
                ->contains(fn ($work) => (int) ($work['production_order_id'] ?? 0) === $productionOrder->id));
    }

    /**
     * Inicia el trabajo del puesto en el turno.
     */
    public function start(Productivity $shift, Model $workstation, ProductionOrder $productionOrder, Rule $rule): array
    {
        return $this->updateWork($shift, $workstation, fn ($work, $locked) => [
            'workstation' => self::key($workstation),
            'production_order_id' => $productionOrder->id,
            'rule_id' => $rule->id,
            'start' => $this->startTime($locked, $workstation)->format('Y-m-d H:i:s'),
            'started_at' => now()->format('Y-m-d H:i:s'),
            'stops' => [],
        ]);
    }

    /**
     * Inicia una parada no programada ahora.
     */
    public function startStop(Productivity $shift, Model $workstation, Stop $stop): array
    {
        return $this->updateWork($shift, $workstation, function ($work) use ($stop) {
            $work['stops'][] = [
                'stop_id' => $stop->id,
                'name' => $stop->name,
                'start' => now()->format('Y-m-d H:i:s'),
                'end' => null,
            ];

            return $work;
        });
    }

    /**
     * Termina la parada no programada abierta ahora.
     */
    public function endStop(Productivity $shift, Model $workstation): array
    {
        return $this->updateWork($shift, $workstation, function ($work) {
            foreach ($work['stops'] as $i => $stop) {
                if (empty($stop['end'])) {
                    $work['stops'][$i]['end'] = now()->format('Y-m-d H:i:s');
                }
            }

            return $work;
        });
    }

    /**
     * Finaliza el trabajo: calcula la eficiencia y crea un ingreso pendiente por destino con las unidades.
     *
     * @param  array  $quantities  [{production_order_detail_id, size_id, quantity}]
     */
    public function finish(Productivity $shift, Model $workstation, Process $process, array $quantities): array
    {
        return DB::transaction(function () use ($shift, $workstation, $process, $quantities) {
            $locked = Productivity::lockForUpdate()->findOrFail($shift->id);
            $work = $this->inProgress($locked, $workstation);
            $rule = Rule::findOrFail($work['rule_id']);

            $summary = $this->summary($locked, $work, now(), $rule);
            $settings = array_merge($summary, ['workstation' => self::key($workstation), 'rule_id' => $rule->id]);

            $details = [];

            foreach (collect($quantities)->groupBy('production_order_detail_id') as $productionOrderDetailId => $sizes) {
                $sizes = $sizes->filter(fn ($size) => (int) $size['quantity'] > 0);

                if ($sizes->isEmpty()) {
                    continue;
                }

                $movement = Movement::firstOrCreate(
                    [
                        'production_order_detail_id' => $productionOrderDetailId,
                        'model_type' => Process::class,
                        'model_id' => $process->id,
                    ],
                    [
                        'status' => 'Ingreso',
                    ]
                );

                $detail = MovementDetail::create([
                    'movement_id' => $movement->id,
                    'model_type' => Productivity::class,
                    'model_id' => $locked->id,
                    'status' => MovementDetail::PENDING,
                    'settings' => $settings,
                ]);

                foreach ($sizes as $size) {
                    MovementDetailQuantity::create([
                        'movement_detail_id' => $detail->id,
                        'size_id' => $size['size_id'],
                        'quantity' => (int) $size['quantity'],
                    ]);
                }

                $detail->rules()->attach($rule->id);

                $details[] = $detail->id;
            }

            $all = $this->settings($locked);
            unset($all['work_in_progress'][self::key($workstation)]);
            $locked->settings = $all;
            $locked->save();

            return array_merge($summary, [
                'units' => (int) collect($quantities)->sum('quantity'),
                'movement_details' => $details,
            ]);
        });
    }

    /**
     * Cambia el trabajo en curso del puesto con el turno bloqueado (dos tablets no se pisan).
     */
    protected function updateWork(Productivity $shift, Model $workstation, callable $change): array
    {
        return DB::transaction(function () use ($shift, $workstation, $change) {
            $locked = Productivity::lockForUpdate()->findOrFail($shift->id);
            $settings = $this->settings($locked);
            $key = self::key($workstation);

            $settings['work_in_progress'][$key] = $change($settings['work_in_progress'][$key] ?? null, $locked);
            $locked->settings = $settings;
            $locked->save();

            return $settings['work_in_progress'][$key];
        });
    }

    /**
     * Settings del turno como arreglo.
     */
    protected function settings(Productivity $shift): array
    {
        return json_decode(json_encode($shift->settings ?? []), true) ?: [];
    }

    /**
     * Fecha del turno con una hora ("07:00" o "07:00:00").
     */
    protected function at(Productivity $shift, string $time): Carbon
    {
        return Carbon::parse($shift->date->format('Y-m-d').' '.$time);
    }

    protected function minutes(Carbon $from, Carbon $to): float
    {
        return $from->diffInSeconds($to) / 60;
    }
}
