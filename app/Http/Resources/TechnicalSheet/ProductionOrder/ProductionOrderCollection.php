<?php

namespace App\Http\Resources\TechnicalSheet\ProductionOrder;

use App\Models\Process;
use App\Services\ProductivityDetailService;
use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductionOrderCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\ProductionOrder::class, $this->collection->pluck('id')->all());
        // Programación del turno: con el proceso y el puesto, cada lote lleva su estimado en ese puesto
        $workstation = $request->filled('process_id') && $request->filled('model_type') ? $request->input('model_type')::find($request->integer('model_id')) : null;
        $process = $workstation ? Process::find($request->integer('process_id')) : null;

        return [
            'production_orders' => $this->collection->map(function ($production_order) use ($workstation, $process, $request, $audited) {
                return [
                    'id' => $production_order->id,
                    'has_audits' => isset($audited[$production_order->id]),
                    'uuid' => $production_order->uuid ?? null,
                    'technical_sheet_id' => $production_order->technical_sheet_id,
                    'production_place' => $production_order->production_place,
                    'supplier_id' => $production_order->supplier_id,
                    'consecutive' => $production_order->consecutive,
                    'efficiency' => $production_order->efficiency,
                    'cut' => $production_order->cut,
                    'width' => $production_order->width,
                    'pocket_fabric' => $production_order->pocket_fabric,
                    'observation' => $production_order->observation,
                    'settings' => $production_order->settings,
                    'status' => $production_order->status,
                    'created_at' => $this->formatDate($production_order->created_at),
                    'updated_at' => $this->formatDate($production_order->updated_at),
                    'deleted_at' => $this->formatDate($production_order->deleted_at),
                    'strokes' => $production_order->strokes,
                    'technical_sheet' => $production_order->technical_sheet,
                    'production_order_details' => $production_order->production_order_details,
                    'fabric' => $production_order->fabric,
                    'pieces' => $production_order->pieces,
                    'supplier' => $production_order->supplier,
                    ...($workstation ? ['estimate' => app(ProductivityDetailService::class)->estimate($production_order->resource, $workstation, $process)] : []),
                    // Anteriores de la cadena donde al lote aún le falta estar programado (la programación lo resuelve en vivo)
                    ...($workstation ? ['previous_missing' => app(ProductivityDetailService::class)
                        ->previousMissing($production_order->resource, $workstation, $process, $request->integer('productivity_id') ?: null)
                        ->map(fn ($previous) => ['model_type' => $previous::class, 'model_id' => $previous->id, 'name' => $previous->name])
                        ->values()] : []),
                    // Puestos que van antes en la cadena del lote: la programación arranca el puesto después de ellos (o a la
                    // vez, con el mismo grupo)
                    ...($workstation ? ['previous' => app(ProductivityDetailService::class)
                        ->chainAncestors($production_order->resource, $workstation, $process)
                        ->map(fn ($previous) => ['model_type' => $previous::class, 'model_id' => $previous->id])
                        ->values()] : []),
                    // Fecha que Gestión de Producción le puso al lote en este proceso: la programación la muestra, no obliga
                    ...($process ? ['planned_date' => $production_order->production_order_details
                        ->first(fn ($detail) => $detail->model_type === Process::class && $detail->model_id === $process->id)
                        ?->settings->date ?? null] : []),
                ];
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    protected function paginationMeta()
    {
        return [
            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage(),
        ];
    }
}
