<?php

namespace App\Http\Resources\Typification\PersonType\DocumentType;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DocumentTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\DocumentType::class, $this->collection->pluck('id')->all());
        return [
            'document_types' => $this->collection->map(function ($document_type) use ($audited) {
                return [
                    'id' => $document_type->id,
                    'has_audits' => isset($audited[$document_type->id]),
                    'item' => $document_type->item,
                    'name' => $document_type->name,
                    'description' => $document_type->description,
                    'created_at' => $this->formatDate($document_type->created_at),
                    'updated_at' => $this->formatDate($document_type->updated_at),
                    'deleted_at' => $this->formatDate($document_type->deleted_at),
                    'settings' => $document_type->settings,
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
