<?php

namespace App\Http\Resources\Typification\SupplyType\Supply;

use App\Services\SupplyService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SupplyResource extends JsonResource
{
    public function toArray($request)
    {
        // Con los desactivados: un color o una talla que se desactivó sigue siendo del insumo y se muestra marcado
        $withTrashed = fn ($query, string $model) => in_array(SoftDeletes::class, class_uses_recursive($model)) ? $query->withTrashed() : $query;
        $form = $this->supply_type->first()?->settings?->form ?? [];

        $relationships = collect($form)
            ->where('type', 'selectdinamic')
            ->mapWithKeys(function ($field) use ($withTrashed) {
                $key = Str::lower(Str::camel(
                    $field->cardinality === 'multiple'
                        ? Str::plural(class_basename($field->model))
                        : class_basename($field->model)
                ));
                $query = $withTrashed($this->related($field->model), $field->model);

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
                'files' => $this->whenLoaded('files'),
                'related' => collect($form)
                    ->where('type', 'selectdinamic')
                    ->mapWithKeys(fn ($field) => [$field->field => $withTrashed($this->related($field->model), $field->model)->get()])
                    ->all(),
                'associated' => collect(app(SupplyService::class)->associatedListFields($form))
                    ->mapWithKeys(fn ($field) => [
                        $field->field => $withTrashed($field->model::query(), $field->model)
                            ->whereIn('id', collect(Arr::wrap(data_get($this->settings, "values.$field->field")))->pluck('ids')->flatten()->unique()->all())
                            ->get(),
                    ])
                    ->all(),
                ...$relationships,
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
