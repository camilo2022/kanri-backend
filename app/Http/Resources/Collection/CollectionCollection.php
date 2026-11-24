<?php

namespace App\Http\Resources\Collection;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CollectionCollection extends ResourceCollection
{
    protected $item;

    public function __construct($resource, $item = null)
    {
        parent::__construct($resource);
        $this->item = $item;
    }

    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Collection::class, $this->collection->pluck('id')->all());
        return [
            'collections' => $this->collection->map(function ($collection) use ($audited) {
                return [
                    'id' => $collection->id,
                    'has_audits' => isset($audited[$collection->id]),
                    'item_id' => $collection->item_id,
                    'name' => $collection->name,
                    'description' => $collection->description,
                    'created_at' => $this->formatDate($collection->created_at),
                    'updated_at' => $this->formatDate($collection->updated_at),
                    'deleted_at' => $this->formatDate($collection->deleted_at),
                    'settings' => $collection->settings,
                    'item' => $collection->item,
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
