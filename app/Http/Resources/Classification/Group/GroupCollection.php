<?php

namespace App\Http\Resources\Classification\Group;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GroupCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'groups' => $this->collection->map(function ($group) {
                return [
                    'id' => $group->id,
                    'item' => $group->item,
                    'name' => $group->name,
                    'description' => $group->description,
                    'created_at' => $this->formatDate($group->created_at),
                    'updated_at' => $this->formatDate($group->updated_at),
                    'deleted_at' => $this->formatDate($group->deleted_at),
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
