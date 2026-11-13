<?php

namespace App\Http\Resources\Typification\SupplierType\Supplier;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Supplier::class, $this->collection->pluck('id')->all());
        return [
            'suppliers' => $this->collection->map(function ($supplier) use ($audited) {
                return [
                    'id' => $supplier->id,
                    'has_audits' => isset($audited[$supplier->id]),
                    'item_id' => $supplier->item_id,
                    'name' => $supplier->name,
                    'description' => $supplier->description,
                    'created_at' => $this->formatDate($supplier->created_at),
                    'updated_at' => $this->formatDate($supplier->updated_at),
                    'deleted_at' => $this->formatDate($supplier->deleted_at),
                    'settings' => $supplier->settings,
                    'item' => $supplier->item,
                    'supplier_type' => $supplier->supplier_type,
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
