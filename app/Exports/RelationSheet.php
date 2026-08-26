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
    protected $supplies;

    public function __construct(SupplyType $supply_type, $field, $supplies)
    {
        $this->supply_type = $supply_type;
        $this->field = $field;
        $this->supplies = $supplies;
    }

    public function collection()
    {
        $rows = collect();

        foreach ($this->supplies as $supply) {

            $relations = $supply
                ->related($this->field->model)
                ->get();

            foreach ($relations as $relation) {
                $rows->push([
                    'supply' => $supply,
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
            $row['supply']->name,
            $this->optionValue($row['relation']) ?: '-',
        ];
    }

    public function headings(): array
    {
        return [
            'supply',
            Str::lower(class_basename($this->field->model)),
        ];
    }

    public function title(): string
    {
        return Str::plural(Str::upper(class_basename($this->field->model)));
    }
}
