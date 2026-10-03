<?php

namespace App\Http\Resources\Management\Production;

use App\Models\ProductionOrder;
use App\Traits\LevelsPriority;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    use LevelsPriority;

    public function toArray($request)
    {
        return [
            'collection' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
                'technical_sheets' => $this->technical_sheets,
            ],
            'status_production_order' => ProductionOrder::statuses(),
            'checks' => $this->getPriorityChecks(),
            'rules' => $this->getPriorityRules(),
            'levels' => $this->getLevelsPriority(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
