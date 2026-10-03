<?php

namespace App\Http\Resources\TechnicalSheet\ProductionOrder;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductionOrderCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'production_orders' => $this->collection->map(function ($production_order) {
                return [
                    'id' => $production_order->id,
                    'uuid' => $production_order->uuid ?? null,
                    'technical_sheet_id' => $production_order->technical_sheet_id,
                    'production_place' => $production_order->production_place,
                    'supplier_id' => $production_order->supplier_id,
                    'consecutive' => $production_order->consecutive,
                    'efficiency' => $production_order->efficiency,
                    'cut' => $production_order->cut,
                    'width' => $production_order->width,
                    'has_pocket_fabric' => $production_order->has_pocket_fabric,
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
