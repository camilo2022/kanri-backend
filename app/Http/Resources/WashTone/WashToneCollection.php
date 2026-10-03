<?php

namespace App\Http\Resources\WashTone;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WashToneCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'wash_tones' => $this->collection->map(function ($wash_tone) {
                return [
                    'id' => $wash_tone->id,
                    'item_id' => $wash_tone->item_id,
                    'name' => $wash_tone->name,
                    'description' => $wash_tone->description,
                    'created_at' => $this->formatDate($wash_tone->created_at),
                    'updated_at' => $this->formatDate($wash_tone->updated_at),
                    'deleted_at' => $this->formatDate($wash_tone->deleted_at),
                    'settings' => $wash_tone->settings,
                    'item' => $wash_tone->item,
                ];
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    protected function paginationMeta()
    {
        return [
            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage(),
        ];
    }
}
