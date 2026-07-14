<?php

namespace App\Exports;

use App\Models\SupplyType;
use App\Models\Variant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Str;

class VariantSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $supply_type;
    protected $variants;

    public function __construct(SupplyType $supply_type, $variants)
    {
        $this->supply_type = $supply_type;
        $this->variants = $variants;
    }

    public function collection()
    {
        return $this->variants;
    }

    public function map($variant): array
    {
        $row = [
            $variant->name ?? '-',
            $variant->description ?? '-',
        ];

        $relationships = collect($this->supply_type->settings->form ?? [])
            ->where('type', 'selectdinamic')
            ->keyBy('field');

        foreach ($this->supply_type->settings->form as $field) {
            if (($field->cardinality ?? '') === 'multiple') {
                continue;
            }

            if (isset($relationships[$field->field])) {
                logger($variant->related($field->model)->first());
                $row[] = data_get($variant->related($field->model)->first(), $field->path, '-') ?? '-';
                continue;
            }

            $row[] = $variant->settings->values->{$field->field} ?? '-';
        }

        return $row;
    }

    public function headings(): array
    {
        $headers = [
            'name',
            'description',
        ];

        foreach ($this->supply_type->settings->form as $field) {

            if (($field->cardinality ?? '') === 'multiple') {
                continue;
            }

            $headers[] = isset($field->model)
                ? Str::lower(class_basename($field->model))
                : $field->field;
        }

        return $headers;
    }

    public function title(): string
    {
        return 'VARIANTS';
    }
}
