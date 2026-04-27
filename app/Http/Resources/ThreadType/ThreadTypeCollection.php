<?php

namespace App\Http\Resources\ThreadType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ThreadTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'thread_types' => $this->collection->map(function ($thread_type) {
                return [
                    'id' => $thread_type->id,
                    'item' => $thread_type->item,
                    'name' => $thread_type->name,
                    'description' => $thread_type->description,
                    'created_at' => $this->formatDate($thread_type->created_at),
                    'updated_at' => $this->formatDate($thread_type->updated_at),
                    'deleted_at' => $this->formatDate($thread_type->deleted_at),
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
