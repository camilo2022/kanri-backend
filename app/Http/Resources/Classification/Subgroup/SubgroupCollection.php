<?php

namespace App\Http\Resources\Classification\Subgroup;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubgroupCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'subgroups' => $this->collection->map(function ($subgroup) {
                return [
                    'id' => $subgroup->id,
                    'item' => $subgroup->item,
                    'name' => $subgroup->name,
                    'description' => $subgroup->description,
                    'created_at' => $this->formatDate($subgroup->created_at),
                    'updated_at' => $this->formatDate($subgroup->updated_at),
                    'deleted_at' => $this->formatDate($subgroup->deleted_at),
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
