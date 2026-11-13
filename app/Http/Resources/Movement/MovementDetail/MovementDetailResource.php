<?php

namespace App\Http\Resources\Movement\MovementDetail;

use App\Models\MovementDetail;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MovementDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'movement_detail' => [
                'id' => $this->id,
                'movement_id' => $this->movement_id,
                'model_type' => $this->model_type,
                'model_id' => $this->model_id,
                'status' => $this->status,
                'observation' => $this->observation,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'movement' => $this->whenLoaded('movement'),
                'model' => $this->whenLoaded('model'),
                'movement_detail_quantities' => $this->whenLoaded('movement_detail_quantities'),
                'movement_detail_redistributions' => $this->whenLoaded('movement_detail_redistributions'),
                'user_created' => $this->whenLoaded('user_created'),
            ],
            'statuses' => MovementDetail::statuses(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
