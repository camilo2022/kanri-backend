<?php

namespace App\Imports;

use App\Models\SupplyType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SupplyImportBuilder
{
    protected Collection $form;

    public array $supplies = [];

    public array $relations = [];

    public array $duplicates = [];

    public function __construct(protected SupplyType $supplyType)
    {
        $this->form = collect($this->supplyType->settings->form ?? []);
    }

    public function addSupply(array $supply): void
    {
        $name = $supply['name'];

        if (isset($this->supplies[$name])) {

            $this->duplicates[] = $name;

            return;
        }

        $this->supplies[$name] = $supply;
    }

    public function addRelation(string $relation, string $supply, mixed $value): void
    {
        $this->relations[$relation][$supply][] = $value;
    }

    public function build(): array
    {
        return collect($this->supplies)
            ->map(fn($supply) => $this->buildSupply($supply))
            ->values()
            ->all();
    }

    private function buildSupply(array $supply): array
    {
        $item = [];

        foreach ($supply as $field => $value) {

            $config = $this->fieldConfig($field);

            if (!$config) {
                $item[$field] = $value;
                continue;
            }

            if (($config->cardinality ?? null) === 'multiple') {
                continue;
            }

            if ($config->type === 'selectdinamic') {
                $item['class'][$field . '_id'] = $this->relationId(
                    $config,
                    $value
                );
            } else {
                $item['settings']['values'][$field] = $value;
            }
        }

        foreach ($this->relations as $field => $supplies) {

            if (!isset($supplies[$supply['name']])) {
                continue;
            }

            $config = $this->fieldConfig($field);

            $item['class'][$field] = collect($supplies[$supply['name']])
                ->map(fn($value) => $this->relationId($config, $value))
                ->filter()
                ->values()
                ->all();
        }

        return $item;
    }

    private function fieldConfig(string $field): ?object
    {
        return $this->form->first(function ($config) use ($field) {


            if ($config->field === $field) {
                return true;
            }

            if (
                $config->type !== 'selectdinamic'
                || !isset($config->model)
            ) {
                return false;
            }

            return Str::lower(class_basename($config->model)) === Str::singular($field);
        });
    }

    private function relationId(object $field, mixed $value): mixed
    {
        if (blank($value) || $value === '-') {
            return null;
        }

        $model = $field->model;

        $path = $field->path ?? 'name';

        if (!str_contains($path, '.')) {
            return $model::query()
                ->where($path, $value)
                ->value('id');
        }

        $parts = explode('.', $path);

        $column = array_pop($parts);

        $relation = implode('.', $parts);

        $instance = new $model;

        $firstRelation = $parts[0];

        if (method_exists($instance, $firstRelation)) {
            return $model::query()
                ->whereRelation($relation, $column, $value)
                ->value('id');
        }

        return $model::query()
            ->where(str_replace('.', '->', $path), $value)
            ->value('id');
    }

    public function duplicateSupplys(): array
    {
        return array_unique($this->duplicates);
    }
}
