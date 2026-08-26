<?php

namespace App\Exports;

use App\Models\SupplyType;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SupplySheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $supply_type;

    protected $supplies;

    public function __construct(SupplyType $supply_type, $supplies)
    {
        $this->supply_type = $supply_type;
        $this->supplies = $supplies;
    }

    public function collection()
    {
        return $this->supplies;
    }

    public function map($supply): array
    {
        $row = [
            $supply->name ?? '-',
            $supply->description ?? '-',
        ];

        $relationships = collect($this->supply_type->settings->form ?? [])
            ->where('type', 'selectdinamic')
            ->keyBy('field');

        foreach ($this->supply_type->settings->form as $field) {
            if (($field->cardinality ?? '') === 'multiple') {
                continue;
            }

            if (isset($relationships[$field->field])) {
                $row[] = data_get($supply->related($field->model)->first(), $field->path, '-') ?? '-';

                continue;
            }

            $row[] = $supply->settings->values->{$field->field} ?? '-';
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
        return 'SUPPLIES';
    }
}
