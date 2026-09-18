<?php

namespace App\Http\Resources\Workflow\Process;

use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessResource extends JsonResource
{
    use ModelTypes;

    public function toArray($request)
    {
        return [
            'process' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
                'before_processes' => $this->before_processes,
                'after_processes' => $this->after_processes,
                'subprocesses' => $this->subprocesses,
                'pivot' => $this->item?->pivot,
            ],
            'model_types' => $this->getModelTypes(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
