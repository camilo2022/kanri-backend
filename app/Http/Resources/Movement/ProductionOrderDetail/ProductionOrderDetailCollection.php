<?php

namespace App\Http\Resources\Movement\ProductionOrderDetail;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductionOrderDetailCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'production_order_details' => $this->collection->map(function ($production_order_detail) {
                return [
                    'id' => $production_order_detail->id,
                    'production_order_id' => $production_order_detail->production_order_id,
                    'destination' => $production_order_detail->destination,
                    'model' => $production_order_detail->model ?? null,
                    'production_order' => $production_order_detail->production_order,
                    'created_at' => $this->formatDate($production_order_detail->created_at),
                    'updated_at' => $this->formatDate($production_order_detail->updated_at),
                    'deleted_at' => $this->formatDate($production_order_detail->deleted_at),
                ];
            }),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
