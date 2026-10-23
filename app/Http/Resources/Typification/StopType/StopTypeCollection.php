<?php

namespace App\Http\Resources\Typification\StopType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StopTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'stop_types' => $this->collection->map(function ($stop_type) {
                return [
                    'id' => $stop_type->id,
                    'item_id' => $stop_type->item_id,
                    'name' => $stop_type->name,
                    'settings' => $stop_type->settings,
                    'description' => $stop_type->description,
                    'created_at' => $this->formatDate($stop_type->created_at),
                    'updated_at' => $this->formatDate($stop_type->updated_at),
                    'deleted_at' => $this->formatDate($stop_type->deleted_at),
                    'item' => $stop_type->item,
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
