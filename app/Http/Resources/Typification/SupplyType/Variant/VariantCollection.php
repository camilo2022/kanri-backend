<?php

namespace App\Http\Resources\Typification\SupplyType\Variant;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

class VariantCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'variants' => $this->collection->map(function ($variant) {
                $relationships = collect($variant->supply_type->first()?->settings?->form ?? [])
                    ->where('type', 'selectdinamic')
                    ->mapWithKeys(function ($field) use ($variant) {
                        $key = Str::lower(Str::camel(
                            class_basename($field->model)
                        ));

                        $query = $variant->related($field->model);

                        return [
                            $key => $field->cardinality === 'single'
                                ? $query->first()
                                : $query->get(),

                            $field->field => $field->cardinality === 'single'
                                ? $query->first()
                                : $query->get()->modelKeys(),
                        ];
                    })
                    ->all();
                return [
                    'id' => $variant->id,
                    'item' => $variant->item,
                    'name' => $variant->name,
                    'description' => $variant->description,
                    'created_at' => $this->formatDate($variant->created_at),
                    'updated_at' => $this->formatDate($variant->updated_at),
                    'deleted_at' => $this->formatDate($variant->deleted_at),
                    'settings' => $variant->settings,
                    'production_orders' => $variant->production_orders,
                    ...$relationships
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
