<?php

namespace App\Http\Resources\Movement;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MovementCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Movement::class, $this->collection->pluck('id')->all());
        return [
            'movements' => $this->collection->map(function ($movement) use ($audited) {
                return [
                    'id' => $movement->id,
                    'has_audits' => isset($audited[$movement->id]),
                    'model' => $movement->model,
                    'production_order_detail' => $movement->production_order_detail,
                    'status' => $movement->status,
                    'created_at' => $this->formatDate($movement->created_at),
                    'updated_at' => $this->formatDate($movement->updated_at),
                    'movement_details_input' => $movement->movement_details_input,
                    'movement_details_output' => $movement->movement_details_output,
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
