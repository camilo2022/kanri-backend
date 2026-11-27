<?php

namespace App\Http\Resources\Product;

use App\Models\TechnicalSheet;
use Carbon\Carbon;
use App\Services\ProductionOrderCurveService;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Product::class, $this->collection->pluck('id')->all());
        $sheet_audited = AuditResolver::auditedIds(TechnicalSheet::class, $this->collection->pluck('technical_sheet.id')->filter()->all());
        $started_orders = array_flip(app(ProductionOrderCurveService::class)->startedOrderIds());

        return [
            'products' => $this->collection->map(function ($product) use ($started_orders, $audited, $sheet_audited) {
                // Reasignación de curva: los modales no ofrecen lotes que ya entraron a trazabilidad o están programados
                foreach (($product->technical_sheet ?? $product->technical_sheets->first())?->production_orders ?? [] as $production_order) {
                    $production_order->in_traceability = isset($started_orders[$production_order->id]);
                }

                return [
                    'id' => $product->id,
                    'has_audits' => isset($audited[$product->id]),
                    'code' => $product->code,
                    'trademark_id' => $product->trademark_id,
                    'subcategory_id' => $product->subcategory_id,
                    'created_at' => $this->formatDate($product->created_at),
                    'updated_at' => $this->formatDate($product->updated_at),
                    'deleted_at' => $this->formatDate($product->deleted_at),
                    'trademark' => $product->trademark,
                    'subcategory' => $product->subcategory,
                    'technical_sheet' => $product->technical_sheet ?? $product->technical_sheets->first(),
                    'original' => $product->technical_sheets->isEmpty(),
                    // Dirección firmada (sin vencimiento) de la ficha de procesos que abre el navegador
                    'process_pdf_url' => $product->technical_sheet?->uuid ? TechnicalSheet::processPdfUrl($product->technical_sheet->uuid, $product->code) : null,
                    'technical_sheet_has_audits' => isset($sheet_audited[$product->technical_sheet?->id]),
                ];
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
            'status_technical_sheet' => TechnicalSheet::statuses(),
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
