<?php

namespace App\Http\Resources\Location;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LocationCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'locations' => $this->collection->map(function ($location) {
                return [
                    'id' => $location->id,
                    'item_id' => $location->item_id,
                    'name' => $location->name,
                    'description' => $location->description,
                    'created_at' => $this->formatDate($location->created_at),
                    'updated_at' => $this->formatDate($location->updated_at),
                    'deleted_at' => $this->formatDate($location->deleted_at),
                    'settings' => $location->settings,
                    'item' => $location->item,
                    'process' => $location->process
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
