<?php

namespace App\Http\Resources\Productivity\ProductivityDetail;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductivityDetailCollection extends ResourceCollection
{
    protected function collects()
    {
        return null;
    }

    public function toArray($request)
    {
        return [
            'productivity_details' => $this->collection->map(function ($productivity_detail) {
                return [
                    'id' => $productivity_detail->id,
                    'productivity_id' => $productivity_detail->productivity_id,
                    'model_type' => $productivity_detail->model_type,
                    'model_id' => $productivity_detail->model_id,
                    'production_order_id' => $productivity_detail->production_order_id,
                    'rule_id' => $productivity_detail->rule_id,
                    'start' => $this->formatDate($productivity_detail->start),
                    'end' => $this->formatDate($productivity_detail->end),
                    'settings' => $productivity_detail->settings,
                    'created_at' => $this->formatDate($productivity_detail->created_at),
                    'updated_at' => $this->formatDate($productivity_detail->updated_at),
                    'productivity' => $productivity_detail->productivity,
                    'model' => $productivity_detail->model,
                    'production_order' => $productivity_detail->production_order,
                    'rule' => $productivity_detail->rule,
                    'employees' => $productivity_detail->employees,
                    'stops' => $productivity_detail->stops,
                    'movement_details' => $productivity_detail->movement_details,
                ];
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
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

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
