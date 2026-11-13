<?php

namespace App\Http\Resources\Categorization\Subcategory;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubcategoryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Subcategory::class, $this->collection->pluck('id')->all());
        return [
            'subcategories' => $this->collection->map(function ($subcategory) use ($audited) {
                return [
                    'id' => $subcategory->id,
                    'has_audits' => isset($audited[$subcategory->id]),
                    'item' => $subcategory->item,
                    'name' => $subcategory->name,
                    'description' => $subcategory->description,
                    'created_at' => $this->formatDate($subcategory->created_at),
                    'updated_at' => $this->formatDate($subcategory->updated_at),
                    'deleted_at' => $this->formatDate($subcategory->deleted_at),
                    'category' => $subcategory->category,
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
