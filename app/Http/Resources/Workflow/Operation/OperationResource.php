<?php

namespace App\Http\Resources\Workflow\Operation;

use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationResource extends JsonResource
{
    use ModelTypes;

    public function toArray($request)
    {
        return [
            'operation' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
                'subprocess' => $this->subprocess,
                'before_operations' => $this->before_operations,
                'after_operations' => $this->after_operations,
                'after_subprocesses' => $this->after_subprocesses,
            ],
            'model_types' => $this->getModelTypes(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
