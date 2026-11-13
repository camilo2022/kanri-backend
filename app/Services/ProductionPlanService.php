<?php

namespace App\Services;

use App\Models\MovementDetail;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\Productivity;
use App\Models\ProductivityDetail;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Plan de Gestión de Producción: la fecha que la programadora pone a cada proceso de un lote.
 */
class ProductionPlanService
{
    public const COMPLETE = 'complete';

    public const PARTIAL = 'partial';

    public const UNWORKED = 'unworked';

    /**
     * Fechas de la colección que ya pasaron sin que el lote terminara el proceso hasta ese día: la fecha se queda y
     * Gestión la muestra en rojo si no se trabajó nada (hay que reprogramarla) o en naranja si se trabajó una parte (solo
     * aviso). Solo en los procesos que miden productividad; en los demás no hay cómo saber si se trabajó.
     *
     * @return array<int, array{production_order_id: int, process_id: int, date: string, status: string, done: array<int, string>, missing: array<int, string>}>
     */
    public function unworkedDates(int $collection_id): array
    {
        return ProductionOrderDetail::with(['production_order' => ['technical_sheet' => ['product']]])
            ->where('model_type', Process::class)
            ->whereNotNull('settings->date')
            ->where('settings->date', '<', now()->format('Y-m-d'))
            ->whereHas('production_order', fn ($order) => $order
                ->whereHas('technical_sheet', fn ($sheet) => $sheet->where('collection_id', $collection_id)))
            ->get()
            // Solo procesos que miden productividad y por los que el lote pasa según su lugar de producción
            ->filter(fn (ProductionOrderDetail $detail) => (Process::find($detail->model_id)?->settings->productivity_measurement ?? null) && $detail->production_order
                && ! app(ProductivityDetailService::class)->branchError($detail->production_order, Process::find($detail->model_id)))
            ->map(fn (ProductionOrderDetail $detail) => array_merge([
                'production_order_id' => $detail->production_order_id,
                'process_id' => $detail->model_id,
                'date' => $detail->settings->date,
                // Para el botón Reprogramar: la referencia con su lote, su código para buscarla y el proceso
                'process' => Process::find($detail->model_id)?->name,
                'code' => $detail->production_order->technical_sheet?->product?->code,
                'reference' => collect([$detail->production_order->technical_sheet?->product?->code, $detail->production_order->cut])->filter()->implode('-'),
            ], $this->progress($detail->production_order, Process::find($detail->model_id), $detail->settings->date)))
            // Trabajada después de su fecha: ya no hay nada que reprogramar
            ->reject(fn ($item) => $item['status'] === self::COMPLETE
                || ($item['status'] === self::UNWORKED && $this->workedInProcess($item['production_order_id'], $item['process_id'])))
            ->values()
            ->all();
    }

    /**
     * ¿El lote tiene algún registro iniciado en el proceso (completo o a medias)? Con trabajo hecho, su fecha ya no se cambia.
     */
    public function workedInProcess(int $production_order_id, int $process_id): bool
    {
        return ProductivityDetail::where('production_order_id', $production_order_id)
            ->whereNotNull('settings->scanned_at')
            ->whereHas('productivity', fn ($shift) => $shift->where('model_type', Process::class)->where('model_id', $process_id))
            ->exists();
    }

    /**
     * Procesos donde cada lote de la colección ya tiene trabajo: la pantalla bloquea su fecha.
     *
     * @return array<int, array{production_order_id: int, process_id: int}>
     */
    public function workedProcesses(int $collection_id): array
    {
        return ProductivityDetail::with('productivity')
            ->whereNotNull('settings->scanned_at')
            ->whereHas('productivity', fn ($shift) => $shift->where('model_type', Process::class))
            ->whereHas('production_order.technical_sheet', fn ($sheet) => $sheet->where('collection_id', $collection_id))
            ->get()
            ->map(fn (ProductivityDetail $detail) => ['production_order_id' => $detail->production_order_id, 'process_id' => $detail->productivity->model_id])
            ->unique(fn ($item) => $item['production_order_id'].':'.$item['process_id'])
            ->values()
            ->all();
    }

    /**
     * Avance del lote en el proceso hasta esa fecha: completo si terminó en todos los puestos de su ruta, parcial si
     * trabajó en alguno, sin trabajar si no se escaneó nada. Un puesto cuenta como hecho con un registro finalizado.
     *
     * @return array{status: string, done: array<int, string>, missing: array<int, string>}
     */
    public function progress(ProductionOrder $production_order, Process $process, string $date): array
    {
        $service = app(ProductivityDetailService::class);
        $rows = ProductivityDetail::where('production_order_id', $production_order->id)
            ->whereNotNull('settings->scanned_at')
            ->whereHas('productivity', fn ($shift) => $shift
                ->where('model_type', Process::class)
                ->where('model_id', $process->id)
                ->whereDate('date', '<=', $date))
            ->get();
        $route = $service->workstations($process)
            ->filter(fn ($item) => $service->routeStep($production_order, $item['model_type']::find($item['model_id']), $process));
        [$done, $missing] = $route->partition(fn ($item) => $rows->contains(fn ($row) => $row->end && $row->model_type === $item['model_type'] && $row->model_id === $item['model_id']));

        return [
            'status' => $rows->isEmpty() ? self::UNWORKED : ($missing->isEmpty() ? self::COMPLETE : self::PARTIAL),
            'done' => $done->pluck('name')->values()->all(),
            'missing' => $missing->pluck('name')->values()->all(),
        ];
    }

    /**
     * Mensaje si no se puede poner esa fecha al proceso: no puede haber pasado y, si es hoy, el turno de hoy del proceso no
     * puede haber terminado (antes del turno o durante él sí se programa para hoy).
     */
    public function dateError(Process $process, string $date): ?string
    {
        $today = now()->format('Y-m-d');

        if ($date < $today) {
            return 'La fecha ya pasó: programa para hoy o después.';
        }

        $shifts = Productivity::where('model_type', Process::class)
            ->where('model_id', $process->id)
            ->whereDate('date', $today)
            ->get();

        if ($date === $today && $shifts->isNotEmpty() && $shifts->every(fn (Productivity $shift) => $shift->endsAt()->lte(now()))) {
            return "El turno de hoy de {$process->name} ya terminó: programa para mañana o después.";
        }

        return null;
    }

    /**
     * Lotes que Gestión tenía planeados para el proceso en esa fecha en un momento dado (el fin del turno). La fecha de
     * hoy puede ser otra: la que tenía entonces sale de la auditoría del registro del proceso.
     */
    public function plannedOrders(Process $process, string $date, Carbon $at): Collection
    {
        return ProductionOrderDetail::with(['production_order' => ['technical_sheet' => ['product']]])
            ->where('model_type', Process::class)
            ->where('model_id', $process->id)
            // Solo lotes que pasan por el proceso según su lugar de producción
            ->whereHas('production_order', fn ($order) => $order->whereNotIn('production_place', app(ProductivityDetailService::class)->excludedPlaces($process)))
            ->where(fn ($query) => $query
                ->where('settings->date', $date)
                ->orWhereHas('audits', fn ($audit) => $audit->where(fn ($values) => $values
                    ->where('old_values', 'like', "%{$date}%")
                    ->orWhere('new_values', 'like', "%{$date}%"))))
            ->get()
            ->filter(fn (ProductionOrderDetail $detail) => $this->dateAt($detail, $at) === $date)
            ->pluck('production_order')
            ->filter()
            ->values();
    }

    /**
     * Fecha que tenía el registro del proceso en ese momento: la de antes del primer cambio posterior, o la actual si no
     * cambió después. Si se creó después, no tenía.
     */
    protected function dateAt(ProductionOrderDetail $detail, Carbon $at): ?string
    {
        $audit = $detail->audits()->where('created_at', '>', $at)->orderBy('created_at')->orderBy('id')->first();

        if (! $audit) {
            return $detail->settings->date ?? null;
        }

        $settings = $audit->old_values['settings'] ?? null;
        $settings = is_string($settings) ? json_decode($settings, true) : (array) $settings;

        return $settings['date'] ?? null;
    }

    /**
     * Lotes de la colección listos para programar, por proceso de Gestión (en el orden de sus columnas). En cada proceso:
     * está En revisión o Aprobado en la ficha y aún no tiene fecha; después del primero (Corte), además ya le llegaron
     * unidades (un ingreso aprobado al proceso).
     *
     * @return array<int, array{process_id: int, process: string, references: array<int, array{production_order_id: int, reference: string, code: ?string}>}>
     */
    public function readyReferences(int $collection_id): array
    {
        return Process::where('settings->in_production_order', true)
            ->get()
            ->map(fn (Process $process) => [
                'process_id' => $process->id,
                'process' => $process->name,
                'references' => ProductionOrder::with(['technical_sheet' => ['product']])
                    ->where('status', '!=', ProductionOrder::CANCELLED)
                    // Solo lotes que pasan por el proceso según su lugar de producción (rama de satélite o de la empresa)
                    ->whereNotIn('production_place', app(ProductivityDetailService::class)->excludedPlaces($process))
                    ->whereHas('technical_sheet', fn ($sheet) => $sheet
                        ->where('collection_id', $collection_id)
                        ->where('status', '!=', TechnicalSheet::PENDING)
                        ->whereHas('technical_sheet_details', fn ($detail) => $detail
                            ->where('model_type', Process::class)
                            ->where('model_id', $process->id)
                            ->whereIn('status', [TechnicalSheetDetail::REVIEW, TechnicalSheetDetail::APPROVED])))
                    ->whereDoesntHave('production_order_details', fn ($detail) => $detail
                        ->where('model_type', Process::class)
                        ->where('model_id', $process->id)
                        ->whereNotNull('settings->date'))
                    ->when(! $process->isFirst(), fn ($query) => $query
                        ->whereHas('production_order_details_product', fn ($detail) => $detail
                            ->whereHas('movements', fn ($movement) => $movement
                                ->where('model_type', Process::class)
                                ->where('model_id', $process->id)
                                ->whereHas('movement_details_input', fn ($input) => $input->where('status', MovementDetail::APPROVED)))))
                    ->get()
                    ->map(fn (ProductionOrder $production_order) => [
                        'production_order_id' => $production_order->id,
                        'reference' => collect([$production_order->technical_sheet?->product?->code, $production_order->cut])->filter()->implode('-'),
                        'code' => $production_order->technical_sheet?->product?->code,
                    ])
                    ->sortBy('reference')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
