<?php

namespace App\Http\Resources\Workflow\Subprocess;

use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SubprocessResource extends JsonResource
{
    use ModelTypes;

    public function toArray($request)
    {
        return [
            'subprocess' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
                'process' => $this->process,
            ],
            'model_types' => $this->getModelTypes(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
