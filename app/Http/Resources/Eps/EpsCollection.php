<?php

namespace App\Http\Resources\Eps;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EpsCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'epss' => $this->collection->map(function ($eps) {
                return [
                    'id' => $eps->id,
                    'item' => $eps->item,
                    'name' => $eps->name,
                    'description' => $eps->description,
                    'created_at' => $this->formatDate($eps->created_at),
                    'updated_at' => $this->formatDate($eps->updated_at),
                    'deleted_at' => $this->formatDate($eps->deleted_at)
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
