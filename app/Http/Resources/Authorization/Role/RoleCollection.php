<?php

namespace App\Http\Resources\Authorization\Role;

use Illuminate\Http\Request;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;

class RoleCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Role::class, $this->collection->pluck('id')->all());
        return [
            'roles' => $this->collection->map(function ($role) use ($audited) {
                return [
                    'id' => $role->id,
                    'has_audits' => isset($audited[$role->id]),
                    'name' => $role->name,
                    'title' => $role->title,
                    'description' => $role->description,
                    'created_at' => $this->formatDate($role->created_at),
                    'updated_at' => $this->formatDate($role->updated_at),
                    'permissions' => $role->permissions,
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
