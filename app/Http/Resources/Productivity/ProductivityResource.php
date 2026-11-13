<?php

namespace App\Http\Resources\Productivity;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductivityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'productivity' => [
                'id' => $this->id,
                'model_type' => $this->model_type,
                'model_id' => $this->model_id,
                'date' => $this->date ? Carbon::parse($this->date)->format('Y-m-d') : null,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'settings' => $this->publicSettings(),
                'duration_hours' => $this->duration_hours,
                'goal_day' => $this->goal_day,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'status' => $this->status,
                'model' => $this->model,
                'productivity_details' => $this->whenLoaded('productivity_details'),
                'planned_orders' => $this->whenLoaded('productivity_details', fn () => $this->plannedOrders()),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
