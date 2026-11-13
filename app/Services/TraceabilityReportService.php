<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\Operation;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\ProductivityDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

    public const TOTAL = ['background' => '#FCE4D6'];

    public function __construct(protected ProductivityDetailService $service) {}

    public function rows(array $filters): array
    {
        $processes = Process::orderBy('id')->get();

        $orders = $this->filtered(ProductionOrder::query(), $filters)
            ->with(['technical_sheet' => ['product', 'color'], 'production_order_details_product' => ['model' => ['trademark', 'subcategory' => ['category']]]])
            ->whereHas('production_order_details.movements')
            ->get()
            ->sortBy(fn (ProductionOrder $order) => collect([data_get($order, 'technical_sheet.product.code'), $order->cut])->filter()->implode('-'))
            ->values();

        $rows = [];

        foreach ($orders as $order) {
            foreach ($this->service->destinations($order) as $destination) {
                array_push($rows, ...$this->destinationRows($order, $destination, $processes, $filters['date'] ?? null));
            }
        }

        return $rows;
    }

    /**
     * Lotes por marca, categoría y colección (los mismos catálogos de los demás reportes).
     */
    protected function filtered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['App\Models\Trademark'] ?? null, fn ($lots, $ids) => $lots->whereHas('technical_sheet.product', fn ($product) => $product->whereIn('trademark_id', $ids)))
            ->when($filters['App\Models\Category'] ?? null, fn ($lots, $ids) => $lots->whereHas('technical_sheet.product.subcategory.category', fn ($category) => $category->whereIn($category->getModel()->getQualifiedKeyName(), $ids)))
            ->when($filters['App\Models\Collection'] ?? null, fn ($lots, $ids) => $lots->whereHas('technical_sheet', fn ($sheet) => $sheet->whereIn('collection_id', $ids)));
    }

    protected function destinationRows(ProductionOrder $order, array $destination, Collection $processes, mixed $date): array
    {
        $pod = $order->production_order_details_product->firstWhere('id', $destination['production_order_detail_id']);
        $curve = (int) collect($destination['sizes'])->sum('quantity');
        $base = [
            'reference' => collect([$destination['reference'], $order->cut])->filter()->implode('-'),
            'destination' => $destination['destination'],
            'category' => data_get($pod, 'model.subcategory.category.0.name'),
            'trademark' => data_get($pod, 'model.trademark.name'),
            'color' => data_get($order, 'technical_sheet.color.name'),
        ];

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
                'destination' => $base['destination'],
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

        return $rows;
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
        $movements = Movement::with([
            'movement_details_input' => fn ($query) => $query->where('status', MovementDetail::APPROVED)->with('movement_detail_quantities'),
            'movement_details_output' => fn ($query) => $query->whereIn('status', [MovementDetail::APPROVED, MovementDetail::PENDING])->with('movement_detail_quantities'),
        ])
            ->where('model_type', Process::class)
            ->where('model_id', $process->id)
            ->where('production_order_detail_id', $pod_id)
            ->get();

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

                $records = ProductivityDetail::where('production_order_id', $order->id)
                    ->where('model_type', $workstation::class)
                    ->where('model_id', $workstation->id)
                    ->whereNotNull('settings->scanned_at');
                $first = (clone $records)->min('start');

                if (! $first) {
                    return null;
                }

                $finished = (clone $records)->whereNotNull('end')->exists();
                // El paso que registró el ingreso del destino guardó lo que produjo por talla
                $produced = MovementDetail::with('movement_detail_quantities')
                    ->whereIn('id', $stage['entry_ids'])
                    ->where('model_type', ProductivityDetail::class)
                    ->whereIn('model_id', (clone $records)->pluck('id'))
                    ->get();

                $done = $this->service->programmable($process, $workstation)
                    ? min($stage['received'], (int) collect($this->service->counters($order, $process, $workstation)['details'])->whereIn('movement_detail_id', $stage['entry_ids'])->sum('produced'))
                    : ($finished ? $stage['received'] : 0);
                $complete = $done >= $stage['received'];

                return [
                    'subprocess' => $step instanceof Operation ? $step->subprocess()->first()?->name : $step->name,
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
