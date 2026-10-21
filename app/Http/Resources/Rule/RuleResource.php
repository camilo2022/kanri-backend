<?php

namespace App\Http\Resources\Rule;

use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class RuleResource extends JsonResource
{
    use ModelTypes;

    public function toArray($request)
    {
        $models = $this->getModelTypesFromRules();

        $class_to_key = collect($models)->mapWithKeys(fn ($config, $key) => [$config['model'] => $key]);

        $relations = DB::table('model_has_subitems')
            ->where('subitem_id', $this->id)
            ->get(['model_type', 'model_id'])
            ->mapWithKeys(function ($row) use ($class_to_key) {
                $model_class = Relation::getMorphedModel($row->model_type) ?? $row->model_type;
                $key = $class_to_key[$model_class] ?? null;
                $model = $key ? $model_class::find($row->model_id) : null;

                return $model ? [$key => $model] : [];
            });

        return [
            'rule' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
                ...$relations,
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
