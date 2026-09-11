<?php

namespace App\Http\Resources\Report;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReportCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'reports' => $this->collection->map(function ($report) {
                return [
                    'id' => $report->id,
                    'item_id' => $report->item_id,
                    'name' => $report->name,
                    'description' => $report->description,
                    'created_at' => $this->formatDate($report->created_at),
                    'updated_at' => $this->formatDate($report->updated_at),
                    'deleted_at' => $this->formatDate($report->deleted_at),
                    'settings' => $report->settings,
                    'item' => $report->item,
                    'permissions' => $report->permissions,
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
