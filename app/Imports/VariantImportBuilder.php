<?php

namespace App\Imports;

use App\Models\SupplyType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VariantImportBuilder
{
    protected Collection $form;

    public array $variants = [];

    public array $relations = [];

    public array $duplicates = [];

    public function __construct(protected SupplyType $supplyType)
    {
        $this->form = collect($this->supplyType->settings->form ?? []);
    }

    public function addVariant(array $variant): void
    {
        $name = $variant['name'];

        if (isset($this->variants[$name])) {

            $this->duplicates[] = $name;

            return;
        }

        $this->variants[$name] = $variant;
    }

    public function addRelation(string $relation, string $variant, mixed $value): void
    {
        $this->relations[$relation][$variant][] = $value;
    }

    public function build(): array
    {
        return collect($this->variants)
            ->map(fn($variant) => $this->buildVariant($variant))
            ->values()
            ->all();
    }

    private function buildVariant(array $variant): array
    {
        $item = [];

        foreach ($variant as $field => $value) {

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

        foreach ($this->relations as $field => $variants) {

            if (!isset($variants[$variant['name']])) {
                continue;
            }

            $config = $this->fieldConfig($field);

            $item['class'][$field] = collect($variants[$variant['name']])
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

    public function duplicateVariants(): array
    {
        return array_unique($this->duplicates);
    }
}
