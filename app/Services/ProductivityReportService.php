<?php

namespace App\Services;

use App\Models\MovementDetailQuantity;
use App\Models\Operation;
use App\Models\ProductivityDetail;
use App\Models\Subprocess;
use Illuminate\Support\Collection;

/**
 * Filas del reporte de productividad (settings.source = productivity en reports): un renglón por registro de trabajo
 * iniciado, agrupado por referencia (lote) y proceso, con tiempos, paradas, minutos-persona, unidades, minutos estándar,
 * eficiencia, meta programada y cumplimiento. Con los estilos de trazabilidad: franja con el último lugar donde se
 * trabajó, fila por proceso con su color en pastel (se despliega en sus registros) y total por referencia. Los tiempos y la eficiencia
 * son los mismos que calcula la estación al finalizar (ProductivityDetailService::summary); no se guarda nada.
 *
 * Cada fila es ['values' => [clave => valor], 'style' => estilo de la fila, 'level' => agrupación en Excel]; ReportController la proyecta a las
 * columnas que eligió el usuario.
 */
class ProductivityReportService
{
    public function __construct(protected ProductivityDetailService $service) {}

    public function rows(array $filters): array
    {
        $from = data_get($filters, 'date.from');
        $to = data_get($filters, 'date.to');

        $details = ProductivityDetail::with([
            'productivity' => ['model'],
            'model',
            'rule',
            'employees' => ['person'],
            'production_order' => ['technical_sheet' => ['product' => ['trademark', 'subcategory' => ['category']]]],
        ])
            ->whereNotNull('settings->scanned_at')
            ->whereHas('productivity', fn ($shift) => $shift
                ->when($from, fn ($query) => $query->whereDate('date', '>=', $from))
                ->when($to, fn ($query) => $query->whereDate('date', '<=', $to)))
            ->whereHas('production_order', fn ($lots) => $lots
                ->when($filters['App\Models\Trademark'] ?? null, fn ($query, $ids) => $query->whereHas('technical_sheet.product', fn ($product) => $product->whereIn('trademark_id', $ids)))
                ->when($filters['App\Models\Category'] ?? null, fn ($query, $ids) => $query->whereHas('technical_sheet.product.subcategory.category', fn ($category) => $category->whereIn($category->getModel()->getQualifiedKeyName(), $ids)))
                ->when($filters['App\Models\Collection'] ?? null, fn ($query, $ids) => $query->whereHas('technical_sheet', fn ($sheet) => $sheet->whereIn('collection_id', $ids))))
            ->get()
            ->filter(fn (ProductivityDetail $detail) => $detail->model && $detail->productivity && $detail->production_order);

        $rows = [];

        foreach ($details->groupBy('production_order_id')->sortBy(fn (Collection $group) => $this->label($group->first())) as $lot) {
            array_push($rows, ...$this->lotRows($lot));
        }

        return $rows;
    }

    protected function lotRows(Collection $lot): array
    {
        $first = $lot->first();
        $order = $first->production_order;
        $base = [
            'reference' => $this->label($first),
            'category' => data_get($order, 'technical_sheet.product.subcategory.category.0.name'),
            'trademark' => data_get($order, 'technical_sheet.product.trademark.name'),
        ];

        $rows = [[
            'values' => array_merge($base, $this->place($lot->sortBy('start')->last())),
            'style' => TraceabilityReportService::HEADER,
            'level' => 0,
        ]];
        $all = collect();

        $processes = $lot->sortBy(fn (ProductivityDetail $detail) => sprintf('%06d-%s', $detail->productivity->model_id, $detail->start))
            ->groupBy(fn (ProductivityDetail $detail) => $detail->productivity->model_id);

        foreach ($processes as $records) {
            $metrics = $records->map(fn (ProductivityDetail $detail) => $this->metrics($detail));
            $all = $all->merge($metrics);

            foreach ($records->values() as $index => $detail) {
                $rows[] = ['values' => array_merge($base, $this->place($detail), $this->display($metrics[$index])), 'style' => null, 'level' => 2];
            }

            $rows[] = [
                'values' => array_merge($base, $this->display($this->sum($metrics)), ['process' => $records->first()->productivity->model->name, 'date' => $metrics->pluck('date')->filter()->min()]),
                'style' => TraceabilityReportService::pastel(data_get($records->first()->productivity->model->settings, 'color')),
                'level' => 1,
            ];
        }

        $rows[] = [
            'values' => array_merge(['reference' => $base['reference'], 'process' => 'TOTAL DE LA REFERENCIA'], $this->display($this->sum($all))),
            'style' => TraceabilityReportService::TOTAL,
            'level' => 0,
        ];

        return $rows;
    }

    /**
     * Tiempos y producción del registro, con la misma cuenta de la estación (hasta su fin o, si sigue en curso, ahora).
     */
    protected function metrics(ProductivityDetail $detail): array
    {
        // Sin +1 (REVISIÓN CORTE registra la curva al finalizar): las unidades son las del ingreso que creó el registro
        $registered = (int) MovementDetailQuantity::whereNotNull('size_id')
            ->whereHas('movement_detail', fn ($query) => $query->where('model_type', ProductivityDetail::class)->where('model_id', $detail->id))
            ->sum('quantity');
        $summary = $this->service->summary($detail, $detail->end ?? now(), $registered ?: null);
        $by_units = $this->service->measuredByUnits($detail->model);
        $goal = $detail->settings->scheduled->units ?? null;
        $units = $by_units ? (int) $summary['units'] : ($goal ?? $this->service->lotUnitsIn($detail->production_order, $detail->productivity->model));

        return [
            'date' => $detail->productivity->date?->format('Y-m-d'),
            'group' => $detail->employees->map(fn ($employee) => $this->service->employeePayload($employee)['name'])->implode(', '),
            'people' => $summary['people'],
            'start' => $detail->start->format('g:i A'),
            'end' => $detail->end?->format('g:i A') ?? 'En curso',
            'total_minutes' => $summary['duration_minutes'],
            'stop_minutes' => $summary['stop_minutes'],
            'worked_minutes' => round($summary['duration_minutes'] - $summary['stop_minutes'], 2),
            'person_minutes' => $summary['worked_minutes'],
            'units' => $units,
            'standard_unit' => $by_units ? round($this->service->standardMinutes($detail, $summary['people']), 2) : null,
            'standard_earned' => $summary['standard_minutes'],
            'goal' => $goal !== null ? (int) $goal : null,
        ];
    }

    protected function sum(Collection $metrics): array
    {
        return [
            'total_minutes' => $metrics->sum('total_minutes'),
            'stop_minutes' => $metrics->sum('stop_minutes'),
            'worked_minutes' => $metrics->sum('worked_minutes'),
            'person_minutes' => $metrics->sum('person_minutes'),
            'units' => $metrics->sum('units'),
            'standard_earned' => $metrics->sum('standard_earned'),
            'goal' => $metrics->whereNotNull('goal')->isNotEmpty() ? $metrics->sum('goal') : null,
        ];
    }

    /**
     * Valores para mostrar: minutos con un decimal, eficiencia = minutos estándar ÷ minutos-persona y cumplimiento =
     * unidades ÷ meta.
     */
    protected function display(array $metrics): array
    {
        $round = fn ($value) => $value === null ? null : round((float) $value, 1);
        $percent = fn ($part, $whole) => $whole > 0 ? number_format($part / $whole * 100, 1).' %' : null;

        return array_merge($metrics, [
            'total_minutes' => $round($metrics['total_minutes'] ?? null),
            'stop_minutes' => $round($metrics['stop_minutes'] ?? null),
            'worked_minutes' => $round($metrics['worked_minutes'] ?? null),
            'person_minutes' => $round($metrics['person_minutes'] ?? null),
            'standard_earned' => $round($metrics['standard_earned'] ?? null),
            'efficiency' => $percent($metrics['standard_earned'] ?? 0, $metrics['person_minutes'] ?? 0),
            'compliance' => isset($metrics['goal']) ? $percent($metrics['units'] ?? 0, $metrics['goal']) : null,
        ]);
    }

    /**
     * Proceso, subproceso y operación del puesto del registro.
     */
    protected function place(ProductivityDetail $detail): array
    {
        $workstation = $detail->model;

        return [
            'process' => $detail->productivity->model->name,
            'subprocess' => match (true) {
                $workstation instanceof Operation => $workstation->subprocess()->first()?->name,
                $workstation instanceof Subprocess => $workstation->name,
                default => null,
            },
            'operation' => $workstation instanceof Operation ? $workstation->name : null,
        ];
    }

    protected function label(ProductivityDetail $detail): string
    {
        return collect([data_get($detail, 'production_order.technical_sheet.product.code'), $detail->production_order?->cut])->filter()->implode('-');
    }
}
