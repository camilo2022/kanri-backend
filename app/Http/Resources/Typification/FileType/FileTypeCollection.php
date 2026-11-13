<?php

namespace App\Http\Resources\Typification\FileType;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FileTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\FileType::class, $this->collection->pluck('id')->all());
        return [
            'file_types' => $this->collection->map(function ($file_type) use ($audited) {
                return [
                    'id' => $file_type->id,
                    'has_audits' => isset($audited[$file_type->id]),
                    'item' => $file_type->item,
                    'name' => $file_type->name,
                    'description' => $file_type->description,
                    'created_at' => $this->formatDate($file_type->created_at),
                    'updated_at' => $this->formatDate($file_type->updated_at),
                    'deleted_at' => $this->formatDate($file_type->deleted_at),
                    'settings' => $file_type->settings,
                    'file_subtypes' => $file_type->file_subtypes,
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
