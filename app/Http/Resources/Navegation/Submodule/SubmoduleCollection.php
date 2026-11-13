<?php

namespace App\Http\Resources\Navegation\Submodule;

use Illuminate\Http\Request;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;

class SubmoduleCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Submodule::class, $this->collection->pluck('id')->all());
        return [
            'submodules' => $this->collection->map(function ($submodule) use ($audited) {
                return [
                    'id' => $submodule->id,
                    'has_audits' => isset($audited[$submodule->id]),
                    'name' => $submodule->name,
                    'url' => $submodule->url,
                    'icon' => $submodule->icon,
                    'created_at' => $this->formatDate($submodule->created_at),
                    'updated_at' => $this->formatDate($submodule->updated_at),
                    'deleted_at' => $this->formatDate($submodule->deleted_at),
                    'module' => $submodule->module,
                    'permission' => $submodule->permission,
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
