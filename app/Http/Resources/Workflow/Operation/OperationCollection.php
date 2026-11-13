<?php

namespace App\Http\Resources\Workflow\Operation;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OperationCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Operation::class, $this->collection->pluck('id')->all());
        return [
            'operations' => $this->collection->map(function ($operation) use ($audited) {
                return [
                    'id' => $operation->id,
                    'has_audits' => isset($audited[$operation->id]),
                    'item_id' => $operation->item_id,
                    'name' => $operation->name,
                    'description' => $operation->description,
                    'created_at' => $this->formatDate($operation->created_at),
                    'updated_at' => $this->formatDate($operation->updated_at),
                    'deleted_at' => $this->formatDate($operation->deleted_at),
                    'settings' => $operation->settings,
                    'item' => $operation->item,
                    'subprocess' => $operation->subprocess,
                    'before_operations' => $operation->before_operations,
                    'after_operations' => $operation->after_operations,
                    'after_subprocesses' => $operation->after_subprocesses,
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
