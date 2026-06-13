<?php

namespace App\Http\Resources\Typification\Variant;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VariantCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'variants' => $this->collection->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'item' => $variant->item,
                    'name' => $variant->name,
                    'description' => $variant->description,
                    'created_at' => $this->formatDate($variant->created_at),
                    'updated_at' => $this->formatDate($variant->updated_at),
                    'deleted_at' => $this->formatDate($variant->deleted_at),
                    'settings' => $variant->settings,
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
