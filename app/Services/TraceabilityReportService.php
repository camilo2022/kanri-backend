<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\Operation;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductivityDetail;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Filas del reporte de trazabilidad (settings.source = traceability en reports), igual que Movimientos: un bloque por
 * referencia y destino (production_order_detail), con la referencia del producto de ese destino. Solo salen los destinos
 * con un ingreso aprobado: lo que importa son los movimientos; un lote a medias en Corte, que aún no lo tiene, se ve en el
 * reporte de productividad. Por cada proceso, una fila con la fecha, la curva del destino, lo recibido, lo despachado
 * (salidas aprobadas y pendientes, repartidas en orden entre los ingresos), el resultado y lo que queda por talla, con el
 * color del proceso en pastel. Esa fila se despliega en los pasos (subproceso u operación) con registro guardado, sin
 * tallas (el +1 no las conoce) salvo el paso que registró el ingreso, con lo que produjo; sin pasos, en sus ingresos si tuvo
 * varios. En los pasos, la curva lleva lo que produjo el paso y lo despachado va vacío. Al final, TOTAL EN PROCESO (solo
 * resultado y tallas).
 *
 * Cada fila es ['values' => [clave => valor], 'style' => estilo de la fila, 'level' => agrupación en Excel];
 * values.sizes trae las tallas [size_id => unidades]. ReportController la proyecta a las columnas que eligió el usuario.
 */
class TraceabilityReportService
{
    public const HEADER = ['background' => '#2F5496', 'color' => '#FFFFFF'];

    public const SUBTOTAL = ['background' => '#D9E1F2'];

    public const TOTAL = ['background' => '#C2C2C2'];

    /**
     * Movimientos por proceso de los destinos del reporte, traídos de una vez: [«destino-proceso» => movimientos].
     */
    protected Collection $movements;

    /**
     * Registros de trabajo escaneados de los lotes del reporte, traídos de una vez: [«lote-modelo-id» => registros].
     */
    protected Collection $records;

    /**
     * Lotes que se arman juntos con una sola consulta de movimientos y registros.
     */
    public const CHUNK = 200;

    /**
     * Columnas que calcula el servicio (values de cada fila); las demás se leen del destino por su data_key. Las tallas
     * van aparte (values.sizes).
     */
    public const COLUMNS = ['reference', 'date', 'process', 'subprocess', 'operation', 'curve', 'received', 'dispatched', 'result', 'status'];

    /**
     * Opciones del filtro calculado inventory: destinos con inventario en un proceso anterior al último (en proceso) o solo
     * en el último (en bodega, ya terminado).
     */
    public const IN_PROCESS = 'in_process';

    public const IN_WAREHOUSE = 'in_warehouse';

    public function __construct(protected ProductivityDetailService $service) {}

    /**
     * Filas del reporte y total de lotes. Con $page solo arma los lotes de esa página (en pantalla); sin ella, todos (Excel).
     * $filters trae solo los filtros que calcula el servicio (fecha de ingreso y procesos a mostrar); los demás llegan en
     * $scope, que los aplica por su data_key sobre los destinos (production_order_details). Cada fila lleva en model su
     * destino, del que ReportController lee las columnas descriptivas por su data_key.
     *
     * @return array{rows: array, lots: int}
     */
    public function rows(array $filters, ?int $page = null, int $per_page = 50, ?Closure $scope = null): array
    {
        $process_ids = $filters[Process::class] ?? null;
        $processes = Process::orderBy('id')->when($process_ids, fn ($query) => $query->whereIn('id', $process_ids))->get();
        $from = data_get($filters, 'date.from');
        $to = data_get($filters, 'date.to');
        // Destinos que cumplen los filtros por data_key y el de inventario (null: todos)
        $allowed = $scope ? $scope(ProductionOrderDetail::query())->pluck('id')->flip() : null;

        if ($filters['inventory'] ?? null) {
            $stock = $this->stockDestinations($filters['inventory']);
            $allowed = $allowed === null ? $stock : $allowed->intersectByKeys($stock);
        }

        $orders = ProductionOrder::query()
            ->with(['technical_sheet' => ['product', 'color']])
            ->whereHas('production_order_details.movements')
            ->when($allowed !== null, fn ($lots) => $lots->whereHas('production_order_details', fn ($pod) => $pod->whereIn('id', $allowed->keys())))
            // Con fechas o procesos, solo los lotes con algún ingreso aprobado que los cumpla (inRange y los procesos elegidos
            // deciden luego por destino): así las páginas no quedan vacías
            ->when($from || $to || $process_ids, fn ($lots) => $lots->whereHas('production_order_details.movements', fn ($movement) => $movement
                ->where('model_type', Process::class)
                ->when($process_ids, fn ($query) => $query->whereIn('model_id', $process_ids))
                ->whereHas('movement_details_input', fn ($detail) => $detail
                    ->where('status', MovementDetail::APPROVED)
                    ->when($from, fn ($query) => $query->whereDate('updated_at', '>=', $from))
                    ->when($to, fn ($query) => $query->whereDate('updated_at', '<=', $to)))))
            ->get()
            ->sortBy(fn (ProductionOrder $order) => collect([data_get($order, 'technical_sheet.product.code'), $order->cut])->filter()->implode('-'))
            ->values();

        $lots = $orders->count();

        if ($page) {
            $orders = $orders->forPage($page, $per_page)->values();
        }

        $rows = [];

        // Por bloques de lotes: una consulta por tipo de dato para cada bloque (en vez de una por destino, proceso y paso) y
        // se suelta antes del siguiente, para que el reporte completo (Excel) no se quede sin memoria
        foreach ($orders->chunk(self::CHUNK) as $chunk) {
            $chunk->load(['production_order_details_product' => ['model' => ['trademark', 'subcategory' => ['category']]]]);

            $this->movements = Movement::with([
                'movement_details_input' => fn ($query) => $query->where('status', MovementDetail::APPROVED)->with('movement_detail_quantities'),
                'movement_details_output' => fn ($query) => $query->whereIn('status', [MovementDetail::APPROVED, MovementDetail::PENDING])->with('movement_detail_quantities'),
            ])
                ->where('model_type', Process::class)
                ->whereIn('production_order_detail_id', $chunk->flatMap->production_order_details_product->pluck('id'))
                ->get()
                ->groupBy(fn (Movement $movement) => $movement->production_order_detail_id.'-'.$movement->model_id);

            $this->records = ProductivityDetail::whereIn('production_order_id', $chunk->pluck('id'))
                ->whereNotNull('settings->scanned_at')
                ->get(['id', 'production_order_id', 'model_type', 'model_id', 'start', 'end'])
                ->groupBy(fn (ProductivityDetail $record) => $record->production_order_id.'-'.$record->model_type.'-'.$record->model_id);

            foreach ($chunk as $order) {
                foreach ($this->service->destinations($order) as $destination) {
                    if ($allowed !== null && ! $allowed->has($destination['production_order_detail_id'])) {
                        continue;
                    }

                    array_push($rows, ...$this->destinationRows($order, $destination, $processes, $filters['date'] ?? null));
                }

                $order->unsetRelation('production_order_details_product');
            }
        }

        $this->movements = collect();
        $this->records = collect();

        return ['rows' => $rows, 'lots' => $lots];
    }

    /**
     * Destinos con inventario según las opciones elegidas, de una vez en la base: por movimiento de proceso, lo recibido
     * (ingresos aprobados) contra lo despachado (salidas aprobadas y pendientes), las mismas cuentas de stage(). El último
     * proceso es el del final del flujo: tiene procesos anteriores y ninguno siguiente (BODEGA).
     *
     * @return Collection<int, int> [destino => índice]
     */
    protected function stockDestinations(array $options): Collection
    {
        $last = Process::get()
            ->filter(fn (Process $process) => $process->before_processes()->exists() && ! $process->after_processes()->exists())
            ->modelKeys();

        $quantities = DB::table('movement_detail_quantities')
            ->whereNotNull('size_id')
            ->groupBy('movement_detail_id')
            ->select('movement_detail_id', DB::raw('SUM(quantity) AS units'));
        $received = DB::table('movement_details')
            ->joinSub($quantities, 'quantities', 'quantities.movement_detail_id', '=', 'movement_details.id')
            ->where('status', MovementDetail::APPROVED)
            ->groupBy('movement_id')
            ->select('movement_id', DB::raw('SUM(units) AS units'));
        $dispatched = DB::table('movement_details')
            ->joinSub($quantities, 'quantities', 'quantities.movement_detail_id', '=', 'movement_details.id')
            ->where('model_type', Movement::class)
            ->whereIn('status', [MovementDetail::APPROVED, MovementDetail::PENDING])
            ->groupBy('model_id')
            ->select('model_id', DB::raw('SUM(units) AS units'));

        $stock = DB::table('movements')
            ->joinSub($received, 'received', 'received.movement_id', '=', 'movements.id')
            ->leftJoinSub($dispatched, 'dispatched', 'dispatched.model_id', '=', 'movements.id')
            ->where('movements.model_type', Process::class)
            ->whereRaw('received.units > COALESCE(dispatched.units, 0)')
            ->get(['movements.production_order_detail_id', 'movements.model_id'])
            ->groupBy('production_order_detail_id')
            ->map(fn ($movements) => $movements->pluck('model_id')->diff($last)->isNotEmpty() ? self::IN_PROCESS : self::IN_WAREHOUSE);

        return $stock->filter(fn ($place) => in_array($place, $options, true))->keys()->flip();
    }

    protected function destinationRows(ProductionOrder $order, array $destination, Collection $processes, mixed $date): array
    {
        $pod = $order->production_order_details_product->firstWhere('id', $destination['production_order_detail_id']);
        $curve = (int) collect($destination['sizes'])->sum('quantity');
        $base = ['reference' => collect([$destination['reference'], $order->cut])->filter()->implode('-')];
        // El destino es el registro de las columnas descriptivas (production_order.technical_sheet…, model…)
        $pod->setRelation('production_order', $order);

        $stages = $processes->map(fn (Process $process) => $this->stage($process, $pod->id))->filter()->values();

        if ($stages->isEmpty() || ! $this->inRange($stages, $date)) {
            return [];
        }

        $steps = $stages->mapWithKeys(fn ($stage) => [$stage['process']->id => $this->stepRows($order, $stage)]);
        // Donde quedó: el último proceso con unidades (o el último al que llegó) y su primer paso registrado sin terminar o,
        // si todos terminaron, el último registrado
        $last = $stages->reverse()->first(fn ($stage) => $stage['received'] > $stage['dispatched']) ?? $stages->last();
        $last_step = collect($steps[$last['process']->id])->firstWhere('complete', false) ?? collect($steps[$last['process']->id])->last();

        $rows = [[
            'values' => array_merge($base, ['process' => $last['process']->name, 'subprocess' => $last_step['subprocess'] ?? null, 'operation' => $last_step['operation'] ?? null]),
            'style' => self::HEADER,
            'level' => 0,
        ]];

        foreach ($stages as $stage) {
            $stage_steps = $steps[$stage['process']->id];

            foreach ($stage_steps ?: (count($stage['entries']) > 1 ? $stage['entries'] : []) as $entry) {
                $rows[] = ['values' => array_merge($base, ['process' => $stage['process']->name], $stage_steps ? [] : ['curve' => $curve], $entry, $this->balance($entry), $stage_steps ? ['curve' => $entry['sizes'] ? array_sum($entry['sizes']) : $entry['dispatched'], 'dispatched' => null] : []), 'style' => null, 'level' => 2];
            }

            $rows[] = [
                'values' => array_merge(
                    $base,
                    ['curve' => $curve, 'sizes' => $stage['sizes']],
                    $stage,
                    $this->balance($stage),
                    ['process' => $stage['process']->name, 'date' => collect($stage['entries'])->pluck('date')->filter()->min()],
                ),
                'style' => self::pastel(data_get($stage['process']->settings, 'color')),
                'level' => 1,
            ];
        }

        $rows[] = [
            'values' => [
                'reference' => $base['reference'],
                'process' => 'TOTAL EN PROCESO',
                'result' => (int) $stages->sum(fn ($stage) => max(0, $stage['received'] - $stage['dispatched'])),
                'sizes' => $stages->reduce(function ($sizes, $stage) {
                    foreach ($stage['sizes'] as $size_id => $units) {
                        $sizes[$size_id] = ($sizes[$size_id] ?? 0) + $units;
                    }

                    return $sizes;
                }, []),
            ],
            'style' => self::TOTAL,
            'level' => 0,
        ];

        return array_map(fn (array $row) => $row + ['model' => $pod], $rows);
    }

    /**
     * Fondo de la fila de un proceso: su color aclarado a pastel; sin color válido, el fondo de siempre.
     */
    public static function pastel(?string $color): array
    {
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $color)) {
            return self::SUBTOTAL;
        }

        return ['background' => vsprintf('#%02X%02X%02X', array_map(fn ($channel) => (int) round(hexdec($channel) + (255 - hexdec($channel)) * 0.75), str_split(substr($color, 1), 2)))];
    }

    /**
     * Lo que pasó el destino por un proceso, según sus movimientos: los ingresos aprobados y lo despachado (salidas aprobadas
     * y pendientes, como en Movimientos), repartido por talla en orden: primero sale lo que entró primero. Null si no
     * tiene ingresos aprobados.
     */
    protected function stage(Process $process, int $pod_id): ?array
    {
        $movements = $this->movements->get($pod_id.'-'.$process->id, collect());

        $inputs = $movements->flatMap->movement_details_input->sortBy('id')->values();

        if ($inputs->isEmpty()) {
            return null;
        }

        $left = $this->bySize($movements->flatMap->movement_details_output);
        $entries = $inputs->map(function (MovementDetail $detail) use (&$left) {
            $sizes = [];
            $out = 0;

            foreach ($this->bySize(collect([$detail])) as $size_id => $units) {
                $taken = min($left[$size_id] ?? 0, $units);
                $left[$size_id] = ($left[$size_id] ?? 0) - $taken;
                $out += $taken;
                $sizes[$size_id] = $units - $taken;
            }

            return ['date' => $detail->updated_at?->format('Y-m-d'), 'received' => array_sum($this->bySize(collect([$detail]))), 'dispatched' => $out, 'sizes' => $sizes];
        });

        $sizes = $entries->reduce(function ($total, $entry) {
            foreach ($entry['sizes'] as $size_id => $units) {
                $total[$size_id] = ($total[$size_id] ?? 0) + $units;
            }

            return $total;
        }, []);

        return [
            'process' => $process,
            'entries' => $entries->all(),
            'entry_ids' => $inputs->pluck('id')->all(),
            'received' => (int) $entries->sum('received'),
            'dispatched' => array_sum($this->bySize($movements->flatMap->movement_details_output)),
            'sizes' => $sizes,
        ];
    }

    /**
     * Unidades por talla de unos detalles de movimiento.
     *
     * @return array<int, int>
     */
    protected function bySize(Collection $details): array
    {
        return $details->flatMap->movement_detail_quantities
            ->whereNotNull('size_id')
            ->groupBy('size_id')
            ->map(fn ($quantities) => (int) $quantities->sum('quantity'))
            ->all();
    }

    /**
     * Pasos con registro guardado del proceso donde está el destino: recibido = lo que llegó al proceso; despachado = lo que
     * hizo el paso.
     */
    protected function stepRows(ProductionOrder $order, array $stage): array
    {
        $process = $stage['process'];

        return $this->service->requiredSteps($order, $process)
            ->reject(fn ($step) => $step instanceof Process)
            ->map(function ($step) use ($order, $process, $stage) {
                $workstation = $this->service->stepWorkstations($step, $process)->first();

                if (! $workstation) {
                    return null;
                }

                $records = $this->records->get($order->id.'-'.$workstation::class.'-'.$workstation->id, collect());
                $first = $records->min('start');

                if (! $first) {
                    return null;
                }

                $finished = $records->whereNotNull('end')->isNotEmpty();
                // El paso que registró el ingreso del destino guardó lo que produjo por talla
                $produced = MovementDetail::with('movement_detail_quantities')
                    ->whereIn('id', $stage['entry_ids'])
                    ->where('model_type', ProductivityDetail::class)
                    ->whereIn('model_id', $records->pluck('id'))
                    ->get();

                $done = $this->service->programmable($process, $workstation)
                    ? min($stage['received'], (int) collect($this->service->counters($order, $process, $workstation)['details'])->whereIn('movement_detail_id', $stage['entry_ids'])->sum('produced'))
                    : ($finished ? $stage['received'] : 0);
                $complete = $done >= $stage['received'];

                return [
                    'subprocess' => $step instanceof Operation ? $this->service->subprocessOf($step)?->name : $step->name,
                    'operation' => $step instanceof Operation ? $step->name : null,
                    'date' => $first ? Carbon::parse($first)->format('Y-m-d') : null,
                    'received' => $stage['received'],
                    'dispatched' => $done,
                    'complete' => $complete,
                    'sizes' => $this->bySize($produced),
                    'status' => $complete ? 'COMPLETADO' : 'EN PROCESO',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Resultado (recibido − despachado) y estado del renglón; los pasos traen su propio estado.
     */
    protected function balance(array $row): array
    {
        $result = max(0, $row['received'] - $row['dispatched']);

        return ['result' => $result, 'status' => $row['status'] ?? ($result > 0 ? 'EN PROCESO' : 'COMPLETADO')];
    }

    /**
     * Filtro de fechas: el destino entra si tuvo algún ingreso en el rango.
     */
    protected function inRange(Collection $stages, mixed $date): bool
    {
        $from = data_get($date, 'from');
        $to = data_get($date, 'to');

        if (! $from && ! $to) {
            return true;
        }

        return $stages->flatMap(fn ($stage) => array_column($stage['entries'], 'date'))
            ->filter()
            ->contains(fn ($day) => (! $from || $day >= $from) && (! $to || $day <= $to));
    }
}
