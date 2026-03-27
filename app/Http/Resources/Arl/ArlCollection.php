<?php

namespace App\Http\Resources\Arl;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ArlCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'arls' => $this->collection->map(function ($arl) {
                return [
                    'id' => $arl->id,
                    'item' => $arl->item,
                    'name' => $arl->name,
                    'description' => $arl->description,
                    'created_at' => $this->formatDate($arl->created_at),
                    'updated_at' => $this->formatDate($arl->updated_at),
                    'deleted_at' => $this->formatDate($arl->deleted_at)
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
