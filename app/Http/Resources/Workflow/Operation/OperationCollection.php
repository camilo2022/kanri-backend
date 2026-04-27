<?php

namespace App\Http\Resources\Workflow\Operation;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OperationCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'operations' => $this->collection->map(function ($operation) {
                return [
                    'id' => $operation->id,
                    'item_id' => $operation->item_id,
                    'name' => $operation->name,
                    'description' => $operation->description,
                    'created_at' => $this->formatDate($operation->created_at),
                    'updated_at' => $this->formatDate($operation->updated_at),
                    'deleted_at' => $this->formatDate($operation->deleted_at),
                    'settings' => $operation->settings,
                    'item' => $operation->item,
                    'subprocess' => $operation->subprocess,
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
