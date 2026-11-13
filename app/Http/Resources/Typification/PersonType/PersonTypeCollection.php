<?php

namespace App\Http\Resources\Typification\PersonType;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PersonTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\PersonType::class, $this->collection->pluck('id')->all());
        return [
            'person_types' => $this->collection->map(function ($person_type) use ($audited) {
                return [
                    'id' => $person_type->id,
                    'has_audits' => isset($audited[$person_type->id]),
                    'item' => $person_type->item,
                    'name' => $person_type->name,
                    'description' => $person_type->description,
                    'created_at' => $this->formatDate($person_type->created_at),
                    'updated_at' => $this->formatDate($person_type->updated_at),
                    'deleted_at' => $this->formatDate($person_type->deleted_at),
                    'settings' => $person_type->settings,
                    'document_types' => $person_type->document_types,
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
