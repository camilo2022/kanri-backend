<?php

namespace App\Http\Resources\Typification\StopType\Stop;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StopCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'stops' => $this->collection->map(function ($stop) {
                return [
                    'id' => $stop->id,
                    'item' => $stop->item,
                    'name' => $stop->name,
                    'description' => $stop->description,
                    'created_at' => $this->formatDate($stop->created_at),
                    'updated_at' => $this->formatDate($stop->updated_at),
                    'deleted_at' => $this->formatDate($stop->deleted_at),
                    'settings' => $stop->settings,
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
