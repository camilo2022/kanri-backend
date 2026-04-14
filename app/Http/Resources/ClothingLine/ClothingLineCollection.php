<?php

namespace App\Http\Resources\ClothingLine;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ClothingLineCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'clothing_lines' => $this->collection->map(function ($clothing_line) {
                return [
                    'id' => $clothing_line->id,
                    'item_id' => $clothing_line->item_id,
                    'name' => $clothing_line->name,
                    'description' => $clothing_line->description,
                    'created_at' => $this->formatDate($clothing_line->created_at),
                    'updated_at' => $this->formatDate($clothing_line->updated_at),
                    'deleted_at' => $this->formatDate($clothing_line->deleted_at),
                    'item' => $clothing_line->item
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
