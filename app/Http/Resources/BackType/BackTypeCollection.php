<?php

namespace App\Http\Resources\BackType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BackTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'back_types' => $this->collection->map(function ($back_type) {
                return [
                    'id' => $back_type->id,
                    'item_id' => $back_type->item_id,
                    'name' => $back_type->name,
                    'description' => $back_type->description,
                    'created_at' => $this->formatDate($back_type->created_at),
                    'updated_at' => $this->formatDate($back_type->updated_at),
                    'deleted_at' => $this->formatDate($back_type->deleted_at),
                    'settings' => $back_type->settings,
                    'item' => $back_type->item,
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
