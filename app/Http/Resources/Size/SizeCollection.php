<?php

namespace App\Http\Resources\Size;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SizeCollection extends ResourceCollection
{
    protected $item;

    public function __construct($resource, $item = null)
    {
        parent::__construct($resource);
        $this->item = $item;
    }

    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Size::class, $this->collection->pluck('id')->all());
        return [
            'sizes' => $this->collection->map(function ($size) use ($audited) {
                return [
                    'id' => $size->id,
                    'has_audits' => isset($audited[$size->id]),
                    'item' => $size->item,
                    'name' => $size->name,
                    'description' => $size->description,
                    'created_at' => $this->formatDate($size->created_at),
                    'updated_at' => $this->formatDate($size->updated_at),
                    'deleted_at' => $this->formatDate($size->deleted_at),
                    'settings' => $size->settings,
                ];
            }),
            'item' => $this->item,
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
