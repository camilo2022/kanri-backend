<?php

namespace App\Http\Resources\BootType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BootTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'boot_types' => $this->collection->map(function ($boot_type) {
                return [
                    'id' => $boot_type->id,
                    'item_id' => $boot_type->item_id,
                    'name' => $boot_type->name,
                    'description' => $boot_type->description,
                    'created_at' => $this->formatDate($boot_type->created_at),
                    'updated_at' => $this->formatDate($boot_type->updated_at),
                    'deleted_at' => $this->formatDate($boot_type->deleted_at),
                    'item' => $boot_type->item,
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
