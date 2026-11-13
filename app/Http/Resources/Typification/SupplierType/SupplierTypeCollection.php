<?php

namespace App\Http\Resources\Typification\SupplierType;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\SupplierType::class, $this->collection->pluck('id')->all());
        return [
            'supplier_types' => $this->collection->map(function ($supplier_type) use ($audited) {
                return [
                    'id' => $supplier_type->id,
                    'has_audits' => isset($audited[$supplier_type->id]),
                    'item_id' => $supplier_type->item_id,
                    'name' => $supplier_type->name,
                    'description' => $supplier_type->description,
                    'created_at' => $this->formatDate($supplier_type->created_at),
                    'updated_at' => $this->formatDate($supplier_type->updated_at),
                    'deleted_at' => $this->formatDate($supplier_type->deleted_at),
                    'settings' => $supplier_type->settings,
                    'item' => $supplier_type->item,
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
