<?php

namespace App\Http\Resources\Authorization\Role;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'role' => [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'title' => $this->title,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'permissions' => $this->permissions
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
