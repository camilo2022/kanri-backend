<?php

namespace App\Http\Resources\Report;

use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\TechnicalSheet;
use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    use ModelTypes;

    public function toArray($request)
    {
        return [
            'report' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
                'permissions' => $this->permissions,
            ],
            'model_type' => $this->getModelTypesFromReports(),
            'statusses' => [
                TechnicalSheet::class => TechnicalSheet::statuses(),
                ProductionOrder::class => ProductionOrder::statuses(),
            ],
            'processes_status' => [
                Process::class => Process::statuses(),
            ],
            'destinations' => ProductionOrder::destinations(),

        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
