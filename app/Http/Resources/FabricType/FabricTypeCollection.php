<?php

namespace App\Http\Resources\FabricType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FabricTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'fabric_types' => $this->collection->map(function ($fabric_type) {
                return [
                    'id' => $fabric_type->id,
                    'item' => $fabric_type->item,
                    'name' => $fabric_type->name,
                    'description' => $fabric_type->description,
                    'created_at' => $this->formatDate($fabric_type->created_at),
                    'updated_at' => $this->formatDate($fabric_type->updated_at),
                    'deleted_at' => $this->formatDate($fabric_type->deleted_at),
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
