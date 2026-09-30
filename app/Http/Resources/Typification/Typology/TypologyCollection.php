<?php

namespace App\Http\Resources\Typification\Typology;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TypologyCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'typologies' => $this->collection->map(function ($typology) {
                return [
                    'id' => $typology->id,
                    'item' => $typology->item,
                    'name' => $typology->name,
                    'description' => $typology->description,
                    'created_at' => $this->formatDate($typology->created_at),
                    'updated_at' => $this->formatDate($typology->updated_at),
                    'deleted_at' => $this->formatDate($typology->deleted_at),
                    'settings' => $typology->settings,
                    'processes' => $typology->supplies,
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
