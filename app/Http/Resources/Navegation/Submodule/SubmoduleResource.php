<?php

namespace App\Http\Resources\Navegation\Submodule;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmoduleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'submodule' => [
                'id' => $this->id,
                'name' => $this->name,
                'icon' => $this->icon,
                'url' => $this->url,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'permission' => $this->permission,
                'module' => $this->module,
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
