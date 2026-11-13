<?php

namespace App\Http\Resources\Typification\SupplyType\Supply;

use Carbon\Carbon;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

class SupplyCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Supply::class, $this->collection->pluck('id')->all());

        return [
            'supplies' => $this->collection->map(function ($supply) use ($audited) {
                $relationships = collect($supply->supply_type->first()?->settings?->form ?? [])
                    ->where('type', 'selectdinamic')
                    ->mapWithKeys(function ($field) use ($supply) {
                        $key = Str::lower(Str::camel(
                            class_basename($field->model)
                        ));

                        $query = $supply->related($field->model);

                        return [
                            /*
                            $key => $field->cardinality === 'single'
                                ? $query->first()
                                : $query->get(),

                            $field->field => $field->cardinality === 'single'
                                ? $query->first()
                                : $query->get()->modelKeys(),
                            */
                            $key => $field->cardinality === 'single' ? $query->first() : $query->get(),
                            $field->field => $field->cardinality === 'single' ? $query->first() : $query->get(),
                        ];
                    })
                    ->all();

                return [
                    'id' => $supply->id,
                    'has_audits' => isset($audited[$supply->id]),
                    'item' => $supply->item,
                    'name' => $supply->name,
                    'description' => $supply->description,
                    'created_at' => $this->formatDate($supply->created_at),
                    'updated_at' => $this->formatDate($supply->updated_at),
                    'deleted_at' => $this->formatDate($supply->deleted_at),
                    'settings' => $supply->settings,
                    'production_order_details' => $supply->production_order_details,
                    ...$relationships,
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
