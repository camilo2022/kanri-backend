<?php

namespace App\Exports;

use App\Models\SupplyType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Str;

class RelationSheet implements FromCollection, WithHeadings, WithMapping, withTitle
{
    protected $supply_type;
    protected $field;
    protected $variants;

    public function __construct(SupplyType $supply_type, $field, $variants)
    {
        $this->supply_type = $supply_type;
        $this->field = $field;
        $this->variants = $variants;
    }

    public function collection()
    {
        $rows = collect();

        foreach ($this->variants as $variant) {

            $relations = $variant
                ->related($this->field->model)
                ->get();

            foreach ($relations as $relation) {
                $rows->push([
                    'variant' => $variant,
                    'relation' => $relation,
                ]);
            }
        }

        return $rows;
    }

    protected function optionValue($model): ?string
    {
        return collect($this->field->path ?? ['name'])
            ->map(fn($path) => data_get($model, $path))
            ->filter()
            ->first();
    }

    public function map($row): array
    {
        return [
            $row['variant']->name,
            $this->optionValue($row['relation']) ?: '-',
        ];
    }

    public function headings(): array
    {
        return [
            'variant',
            Str::lower(class_basename($this->field->model)),
        ];
    }

    public function title(): string
    {
        return Str::plural(Str::upper(class_basename($this->field->model)));
    }
}
