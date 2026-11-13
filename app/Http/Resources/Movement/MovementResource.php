<?php

namespace App\Http\Resources\Movement;

use App\Models\MovementDetail;
use App\Services\ProductivityDetailService;
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
                // Lo que se puede trasladar a otro proceso: lo que ya pasó por todos los pasos de la ficha − lo
                // trasladado (null: el proceso no mide productividad, sin tope)
                'transferable' => app(ProductivityDetailService::class)->transferable($this->resource),
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
