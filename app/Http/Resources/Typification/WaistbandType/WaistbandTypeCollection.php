<?php

namespace App\Http\Resources\Typification\WaistbandType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WaistbandTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'waistband_types' => $this->collection->map(function ($waistband_type) {
                return [
                    'id' => $waistband_type->id,
                    'item_id' => $waistband_type->item_id,
                    'name' => $waistband_type->name,
                    'description' => $waistband_type->description,
                    'created_at' => $this->formatDate($waistband_type->created_at),
                    'updated_at' => $this->formatDate($waistband_type->updated_at),
                    'deleted_at' => $this->formatDate($waistband_type->deleted_at),
                    'settings' => $waistband_type->settings,
                    'item' => $waistband_type->item,
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
