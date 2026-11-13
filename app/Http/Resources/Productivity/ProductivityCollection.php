<?php

namespace App\Http\Resources\Productivity;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductivityCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'productivities' => $this->collection->map(function ($productivity) {
                return [
                    'id' => $productivity->id,
                    'model_type' => $productivity->model_type,
                    'model_id' => $productivity->model_id,
                    'date' => $productivity->date ? Carbon::parse($productivity->date)->format('Y-m-d') : null,
                    'start_time' => $productivity->start_time,
                    'end_time' => $productivity->end_time,
                    'settings' => $productivity->publicSettings(),
                    'duration_hours' => $productivity->duration_hours,
                    'goal_day' => $productivity->goal_day,
                    'productivity_details' => $productivity->productivity_details,
                    'created_at' => $this->formatDate($productivity->created_at),
                    'updated_at' => $this->formatDate($productivity->updated_at),
                    'deleted_at' => $this->formatDate($productivity->deleted_at),
                    'status' => $productivity->status,
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
