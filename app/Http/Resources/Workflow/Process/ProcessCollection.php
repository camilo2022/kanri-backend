<?php

namespace App\Http\Resources\Workflow\Process;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProcessCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'processes' => $this->collection->map(function ($process) {
                return [
                    'id' => $process->id,
                    'item_id' => $process->item_id,
                    'name' => $process->name,
                    'description' => $process->description,
                    'created_at' => $this->formatDate($process->created_at),
                    'updated_at' => $this->formatDate($process->updated_at),
                    'deleted_at' => $this->formatDate($process->deleted_at),
                    'settings' => $process->settings,
                    'before_processes' => $process->before_processes,
                    'after_processes' => $process->after_processes,
                    'subprocesses' => $process->subprocesses,
                    'item' => $process->item,
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
