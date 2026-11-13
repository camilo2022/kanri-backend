<?php

namespace App\Http\Resources\Typification\StopType\Stop;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StopCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Stop::class, $this->collection->pluck('id')->all());
        return [
            'stops' => $this->collection->map(function ($stop) use ($audited) {
                return [
                    'id' => $stop->id,
                    'has_audits' => isset($audited[$stop->id]),
                    'item' => $stop->item,
                    'name' => $stop->name,
                    'description' => $stop->description,
                    'created_at' => $this->formatDate($stop->created_at),
                    'updated_at' => $this->formatDate($stop->updated_at),
                    'deleted_at' => $this->formatDate($stop->deleted_at),
                    'settings' => $stop->settings,
                    'stop_type' => $stop->stop_type,
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
