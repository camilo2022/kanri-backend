<?php

namespace App\Http\Resources\Movement;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MovementCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'movements' => $this->collection->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'model' => $movement->model,
                    'production_order_detail' => $movement->production_order_detail,
                    'status' => $movement->status,
                    'created_at' => $this->formatDate($movement->created_at),
                    'updated_at' => $this->formatDate($movement->updated_at),
                    'deleted_at' => $this->formatDate($movement->deleted_at),
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
