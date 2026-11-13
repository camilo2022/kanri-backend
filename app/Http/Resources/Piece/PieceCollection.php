<?php

namespace App\Http\Resources\Piece;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PieceCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Piece::class, $this->collection->pluck('id')->all());
        return [
            'pieces' => $this->collection->map(function ($piece) use ($audited) {
                return [
                    'id' => $piece->id,
                    'has_audits' => isset($audited[$piece->id]),
                    'item_id' => $piece->item_id,
                    'name' => $piece->name,
                    'description' => $piece->description,
                    'created_at' => $this->formatDate($piece->created_at),
                    'updated_at' => $this->formatDate($piece->updated_at),
                    'deleted_at' => $this->formatDate($piece->deleted_at),
                    'item' => $piece->item
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
