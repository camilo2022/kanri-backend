<?php

namespace App\Http\Resources\YokeType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class YokeTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'yoke_types' => $this->collection->map(function ($yoke_type) {
                return [
                    'id' => $yoke_type->id,
                    'item_id' => $yoke_type->item_id,
                    'name' => $yoke_type->name,
                    'description' => $yoke_type->description,
                    'settings' => $yoke_type->settings,
                    'created_at' => $this->formatDate($yoke_type->created_at),
                    'updated_at' => $this->formatDate($yoke_type->updated_at),
                    'deleted_at' => $this->formatDate($yoke_type->deleted_at),
                    'item' => $yoke_type->item,
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
