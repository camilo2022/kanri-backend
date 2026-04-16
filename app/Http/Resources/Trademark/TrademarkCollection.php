<?php

namespace App\Http\Resources\Trademark;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrademarkCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'trademarks' => $this->collection->map(function ($trademark) {
                return [
                    'id' => $trademark->id,
                    'item' => $trademark->item,
                    'name' => $trademark->name,
                    'description' => $trademark->description,
                    'settings' => $trademark->settings,
                    'created_at' => $this->formatDate($trademark->created_at),
                    'updated_at' => $this->formatDate($trademark->updated_at),
                    'deleted_at' => $this->formatDate($trademark->deleted_at),
                    'category' => $trademark->category
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
