<?php

namespace App\Http\Resources\GarmentType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GarmentTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'garment_types' => $this->collection->map(function ($garment_type) {
                return [
                    'id' => $garment_type->id,
                    'item' => $garment_type->item,
                    'name' => $garment_type->name,
                    'description' => $garment_type->description,
                    'created_at' => $this->formatDate($garment_type->created_at),
                    'updated_at' => $this->formatDate($garment_type->updated_at),
                    'deleted_at' => $this->formatDate($garment_type->deleted_at),
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
