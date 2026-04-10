<?php

namespace App\Http\Resources\Categorization\Category;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'categories' => $this->collection->map(function ($category) {
                return [
                    'id' => $category->id,
                    'item' => $category->item,
                    'name' => $category->name,
                    'description' => $category->description,
                    'created_at' => $this->formatDate($category->created_at),
                    'updated_at' => $this->formatDate($category->updated_at),
                    'deleted_at' => $this->formatDate($category->deleted_at),
                    'positions' => $category->positions,
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
