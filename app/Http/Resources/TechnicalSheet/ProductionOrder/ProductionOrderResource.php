<?php

namespace App\Http\Resources\TechnicalSheet;

use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'production_order' => [
                'id' => $this->id,
                'model_id' => $this->model_id,
                'model_type' => $this->model_type,
                'technical_sheet_id' => $this->technical_sheet_id,
                'date' => $this->date,
                'consecutive' => $this->consecutive,
                'efficiency' => $this->efficiency,
                'cut' => $this->cut,
                'fabric_id' => $this->fabric_id,
                'width' => $this->width,
                'pocket_fabric' => $this->pocket_fabric,
                'observation' => $this->observation,
                'settings' => $this->settings,
                'status' => $this->status,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'technical_sheet' => $this->technical_sheet,
                'fabric' => $this->fabric,
            ],
            'status_production_order' => ProductionOrder::statuses()
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
