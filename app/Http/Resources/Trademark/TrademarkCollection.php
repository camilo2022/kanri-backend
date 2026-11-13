<?php

namespace App\Http\Resources\Trademark;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrademarkCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Trademark::class, $this->collection->pluck('id')->all());
        return [
            'trademarks' => $this->collection->map(function ($trademark) use ($audited) {
                return [
                    'id' => $trademark->id,
                    'has_audits' => isset($audited[$trademark->id]),
                    'item' => $trademark->item,
                    'name' => $trademark->name,
                    'description' => $trademark->description,
                    'settings' => $trademark->settings,
                    'logo' => $trademark->logo,
                    'cover' => $trademark->cover,
                    'created_at' => $this->formatDate($trademark->created_at),
                    'updated_at' => $this->formatDate($trademark->updated_at),
                    'deleted_at' => $this->formatDate($trademark->deleted_at),
                    'group' => $trademark->group,
                    'sizes' => $trademark->sizes,
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
