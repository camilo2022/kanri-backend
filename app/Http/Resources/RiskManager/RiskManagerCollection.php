<?php

namespace App\Http\Resources\RiskManager;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RiskManagerCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\RiskManager::class, $this->collection->pluck('id')->all());
        return [
            'risk_managers' => $this->collection->map(function ($risk_manager) use ($audited) {
                return [
                    'id' => $risk_manager->id,
                    'has_audits' => isset($audited[$risk_manager->id]),
                    'item_id' => $risk_manager->item_id,
                    'name' => $risk_manager->name,
                    'description' => $risk_manager->description,
                    'created_at' => $this->formatDate($risk_manager->created_at),
                    'updated_at' => $this->formatDate($risk_manager->updated_at),
                    'deleted_at' => $this->formatDate($risk_manager->deleted_at),
                    'item' => $risk_manager->item,
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
