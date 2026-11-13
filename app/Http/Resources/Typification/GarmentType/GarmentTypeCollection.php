<?php

namespace App\Http\Resources\Typification\GarmentType;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GarmentTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\GarmentType::class, $this->collection->pluck('id')->all());
        return [
            'garment_types' => $this->collection->map(function ($garment_type) use ($audited) {
                return [
                    'id' => $garment_type->id,
                    'has_audits' => isset($audited[$garment_type->id]),
                    'item_id' => $garment_type->item_id,
                    'name' => $garment_type->name,
                    'description' => $garment_type->description,
                    'created_at' => $this->formatDate($garment_type->created_at),
                    'updated_at' => $this->formatDate($garment_type->updated_at),
                    'deleted_at' => $this->formatDate($garment_type->deleted_at),
                    'settings' => $garment_type->settings,
                    'item' => $garment_type->item,
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
