<?php

namespace App\Http\Resources\Workflow\Process;

use App\Models\Process;
use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProcessCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Process::class, $this->collection->pluck('id')->all());
        return [
            'processes' => $this->collection->map(function ($process) use ($request, $audited) {
                return [
                    'id' => $process->id,
                    'has_audits' => isset($audited[$process->id]),
                    'item_id' => $process->item_id,
                    'model_type' => Process::class,
                    'name' => $process->name,
                    'description' => $process->description,
                    'created_at' => $this->formatDate($process->created_at),
                    'updated_at' => $this->formatDate($process->updated_at),
                    'deleted_at' => $this->formatDate($process->deleted_at),
                    'settings' => $process->settings,
                    'before_processes' => $process->before_processes,
                    'after_processes' => $process->after_processes,
                    'subprocesses' => $request->boolean('with_trashed_children') ? $this->subprocessesWithTrashed($process) : $process->subprocesses,
                    'item' => $process->item,
                    // Cronograma de producción: leyenda y filtro con las tipologías de cada proceso
                    ...($request->boolean('with_typologies') ? ['typologies' => $process->typologies] : []),
                ];
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
    }

    /**
     * Subprocesos y operaciones con los desactivados, con las mismas claves de siempre (subprocesses y operations).
     */
    protected function subprocessesWithTrashed($process)
    {
        return $process->subprocesses_with_trashed->each(function ($subprocess) {
            $subprocess->setRelation('operations', $subprocess->operations_with_trashed);
            $subprocess->unsetRelation('operations_with_trashed');
        });
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
