<?php

namespace App\Http\Resources\Typification\SupplyType\Supply;

use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SupplyResource extends JsonResource
{
    use ModelTypes;

    public function toArray($request)
    {
        $relationships = collect($this->supply_type->first()?->settings?->form ?? [])
            ->where('type', 'selectdinamic')
            ->mapWithKeys(function ($field) {
                $key = Str::lower(Str::camel(
                    $field->cardinality === 'multiple'
                        ? Str::plural(class_basename($field->model))
                        : class_basename($field->model)
                ));
                $query = $this->related($field->model);

                return [
                    $key => $field->cardinality === 'single' ? $query->first() : $query->get(),
                    $field->field => $field->cardinality === 'single' ? $query->first()?->id : $query->get()->modelKeys(),
                ];
            })
            ->all();

        return [
            'supply' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
                'supply_type' => $this->supply_type->first(),
                ...$relationships,
            ],
            'model_types' => $this->getModelTypesFromSupply(),
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
