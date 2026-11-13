<?php

namespace App\Http\Resources\Rule;

use App\Traits\ModelTypes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Support\AuditResolver;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class RuleCollection extends ResourceCollection
{
    use ModelTypes;

    protected $item;

    public function __construct($resource, $item)
    {
        parent::__construct($resource);
        $this->item = $item;
    }

    public function toArray($request)
    {
        $audited = AuditResolver::auditedIds(\App\Models\Rule::class, $this->collection->pluck('id')->all());
        $models = $this->getModelTypesFromRules();

        $class_to_key = collect($models)->mapWithKeys(fn ($config, $key) => [$config['model'] => $key]);

        $pivot_rows = DB::table('model_has_subitems')
            ->whereIn('subitem_id', $this->collection->pluck('id'))
            ->get(['subitem_id', 'model_type', 'model_id'])
            ->map(function ($row) {
                $row->model_class = Relation::getMorphedModel($row->model_type) ?? $row->model_type;

                return $row;
            });

        $loaded_models = $pivot_rows
            ->groupBy('model_class')
            ->map(fn ($rows, $model_class) => $model_class::whereKey($rows->pluck('model_id')->unique())
                ->get()
                ->keyBy(fn ($model) => $model->getKey()));

        $pivot_by_rule = $pivot_rows->groupBy('subitem_id');

        return [
            'rules' => $this->collection->map(function ($rule) use ($pivot_by_rule, $loaded_models, $class_to_key, $audited) {
                $relations = collect($pivot_by_rule[$rule->id] ?? [])
                    ->mapWithKeys(function ($row) use ($loaded_models, $class_to_key) {
                        $key = $class_to_key[$row->model_class] ?? null;
                        $model = $loaded_models[$row->model_class][$row->model_id] ?? null;

                        return $key && $model ? [$key => $model] : [];
                    });

                return [
                    'id' => $rule->id,
                    'has_audits' => isset($audited[$rule->id]),
                    'item_id' => $rule->item_id,
                    'name' => $rule->name,
                    'description' => $rule->description,
                    'created_at' => $this->formatDate($rule->created_at),
                    'updated_at' => $this->formatDate($rule->updated_at),
                    'deleted_at' => $this->formatDate($rule->deleted_at),
                    'settings' => $rule->settings,
                    ...$relations,
                ];
            }),
            'item' => $this->item,
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
            'model_types' => $models,
        ];
    }

    protected function formatDate($date)
    {
        return $date
            ? Carbon::parse($date)->format('Y-m-d H:i:s')
            : null;
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
