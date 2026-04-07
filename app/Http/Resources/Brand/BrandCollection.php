<?php

namespace App\Http\Resources\Brand;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BrandCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'brands' => $this->collection->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'item' => $brand->item,
                    'name' => $brand->name,
                    'description' => $brand->description,
                    'created_at' => $this->formatDate($brand->created_at),
                    'updated_at' => $this->formatDate($brand->updated_at),
                    'deleted_at' => $this->formatDate($brand->deleted_at)
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
