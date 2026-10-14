<?php

namespace App\Http\Resources\Movement;

use App\Models\MovementDetail;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MovementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'movement' => [
                'id' => $this->id,
                'model_id' => $this->model_id,
                'model_type' => $this->model_type,
                'model' => $this->model,
                'production_order_detail_id' => $this->production_order_detail_id,
                'status' => $this->status,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'production_order_detail' => $this->production_order_detail,
                'movement_details_input' => $this->movement_details_input,
                'movement_details_output' => $this->movement_details_output,
            ],
            'statuses' => MovementDetail::statuses(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    protected function formatDateMovement($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d') : null;
    }
}
