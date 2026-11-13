<?php

namespace App\Http\Resources\Typification\BackType;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BackTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\BackType::class, $this->collection->pluck('id')->all());
        return [
            'back_types' => $this->collection->map(function ($back_type) use ($audited) {
                return [
                    'id' => $back_type->id,
                    'has_audits' => isset($audited[$back_type->id]),
                    'item_id' => $back_type->item_id,
                    'name' => $back_type->name,
                    'description' => $back_type->description,
                    'created_at' => $this->formatDate($back_type->created_at),
                    'updated_at' => $this->formatDate($back_type->updated_at),
                    'deleted_at' => $this->formatDate($back_type->deleted_at),
                    'settings' => $back_type->settings,
                    'item' => $back_type->item,
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
