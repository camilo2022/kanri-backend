<?php

namespace App\Http\Resources\Workflow\Subprocess;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubprocessCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'subprocesses' => $this->collection->map(function ($subprocess) {
                return [
                    'id' => $subprocess->id,
                    'item_id' => $subprocess->item_id,
                    'name' => $subprocess->name,
                    'description' => $subprocess->description,
                    'created_at' => $this->formatDate($subprocess->created_at),
                    'updated_at' => $this->formatDate($subprocess->updated_at),
                    'deleted_at' => $this->formatDate($subprocess->deleted_at),
                    'item' => $subprocess->item,
                    'process' => $subprocess->process,
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
