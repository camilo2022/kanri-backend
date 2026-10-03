<?php

namespace App\Http\Resources\Builder;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class BuilderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'builder' => [
                'id' => $this['id'],
                'trademark_ids' => $this['trademark_ids'],
                'category_ids' => $this['category_ids'],
                'subcategory_ids' => $this['subcategory_ids'],
                'percentages' => $this['percentages'],
            ]
        ];
    }
}
